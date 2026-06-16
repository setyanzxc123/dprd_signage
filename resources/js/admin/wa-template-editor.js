import { Schema } from 'prosemirror-model';
import { schema as basicSchema } from 'prosemirror-schema-basic';
import { EditorState, Plugin } from 'prosemirror-state';
import { EditorView, Decoration, DecorationSet } from 'prosemirror-view';
import { toggleMark } from 'prosemirror-commands';
import { history, undo, redo } from 'prosemirror-history';
import { keymap } from 'prosemirror-keymap';
import { baseKeymap } from 'prosemirror-commands';

const placeholderNode = {
    inline: true,
    group: 'inline',
    marks: '',
    atom: true,
    selectable: false,
    attrs: {
        token: {},
        label: {},
    },
    toDOM(node) {
        return ['span', {
            class: 'wa-var',
            'data-token': node.attrs.token,
            contenteditable: 'false',
        }, node.attrs.label || node.attrs.token];
    },
};

const strikeMark = {
    parseDOM: [
        { tag: 's' },
        { tag: 'del' },
        { tag: 'strike' },
        { style: 'text-decoration=line-through' },
    ],
    toDOM() {
        return ['del', 0];
    },
};

const schema = new Schema({
    nodes: basicSchema.spec.nodes.addBefore('text', 'placeholder', placeholderNode),
    marks: basicSchema.spec.marks.addToEnd('strike', strikeMark),
});

let activeEditor = null;

function addMark(marks, markName) {
    const markType = schema.marks[markName];
    return markType ? markType.create().addToSet(marks) : marks;
}

function findClosing(text, marker, start) {
    const index = text.indexOf(marker, start);
    return index >= 0 ? index : -1;
}

function parseInline(text, labels, marks = []) {
    const nodes = [];
    let buffer = '';

    function flush() {
        if (buffer !== '') {
            nodes.push(schema.text(buffer, marks));
            buffer = '';
        }
    }

    for (let i = 0; i < text.length;) {
        const placeholder = text.slice(i).match(/^\{([a-zA-Z0-9_]+)\}/);
        if (placeholder) {
            flush();
            const token = placeholder[1];
            nodes.push(schema.nodes.placeholder.create({
                token,
                label: labels[token] || token,
            }));
            i += placeholder[0].length;
            continue;
        }

        if (text.startsWith('```', i)) {
            const end = findClosing(text, '```', i + 3);
            if (end > i) {
                flush();
                nodes.push(...parseInline(text.slice(i + 3, end), labels, addMark(marks, 'code')));
                i = end + 3;
                continue;
            }
        }

        const markMap = { '*': 'strong', '_': 'em', '~': 'strike' };
        const markName = markMap[text[i]];
        if (markName) {
            const end = findClosing(text, text[i], i + 1);
            if (end > i) {
                flush();
                nodes.push(...parseInline(text.slice(i + 1, end), labels, addMark(marks, markName)));
                i = end + 1;
                continue;
            }
        }

        buffer += text[i];
        i += 1;
    }

    flush();
    return nodes;
}

function textToDoc(waText, labels) {
    const lines = (waText || '').split('\n');
    const paragraphs = lines.map((line) => schema.nodes.paragraph.create(null, parseInline(line, labels)));
    return schema.nodes.doc.create(null, paragraphs.length ? paragraphs : [schema.nodes.paragraph.create()]);
}

const markMarkers = {
    strong: '*',
    em: '_',
    strike: '~',
    code: '```',
};

const serialMarkOrder = ['strong', 'em', 'strike', 'code'];

function serializeInlineContent(node) {
    if (node.isText) {
        return node.text || '';
    }
    if (node.type.name === 'placeholder') {
        return `{${node.attrs.token}}`;
    }
    if (node.type.name === 'hard_break') {
        return '\n';
    }
    return serializeInlineRun(node);
}

function nodeMarkNames(node) {
    const names = new Set((node.marks || []).map((mark) => mark.type.name));
    return serialMarkOrder.filter((markName) => names.has(markName));
}

function sharedPrefixLength(left, right) {
    let count = 0;
    while (left[count] && left[count] === right[count]) {
        count += 1;
    }
    return count;
}

function serializeInlineRun(parent) {
    let text = '';
    let activeMarks = [];

    parent.forEach((child) => {
        const nextMarks = nodeMarkNames(child);
        const keep = sharedPrefixLength(activeMarks, nextMarks);

        for (let i = activeMarks.length - 1; i >= keep; i -= 1) {
            text += markMarkers[activeMarks[i]];
        }
        for (let i = keep; i < nextMarks.length; i += 1) {
            text += markMarkers[nextMarks[i]];
        }

        text += serializeInlineContent(child);
        activeMarks = nextMarks;
    });

    for (let i = activeMarks.length - 1; i >= 0; i -= 1) {
        text += markMarkers[activeMarks[i]];
    }
    return text;
}

function docToText(doc) {
    const lines = [];
    doc.forEach((block) => {
        lines.push(serializeInlineRun(block));
    });
    return lines.join('\n').replace(/\n+$/, '');
}

function markActive(state, markName) {
    const markType = schema.marks[markName];
    if (!markType) return false;

    const { from, to, empty } = state.selection;
    if (empty) {
        return !!markType.isInSet(state.storedMarks || state.selection.$from.marks());
    }

    return state.doc.rangeHasMark(from, to, markType);
}

function activeMarks(view) {
    if (!view) return {};
    return {
        strong: markActive(view.state, 'strong'),
        em: markActive(view.state, 'em'),
        strike: markActive(view.state, 'strike'),
        code: markActive(view.state, 'code'),
    };
}

function docIsEmpty(doc) {
    let empty = true;
    doc.descendants((node) => {
        if ((node.isText && node.text) || node.type.name === 'placeholder') {
            empty = false;
            return false;
        }
        return empty;
    });
    return empty;
}

function placeholderPlugin(text) {
    return new Plugin({
        props: {
            decorations(state) {
                if (!docIsEmpty(state.doc)) return null;
                const widget = document.createElement('span');
                widget.className = 'wa-editor-placeholder';
                widget.textContent = text;
                return DecorationSet.create(state.doc, [Decoration.widget(1, widget, { side: -1 })]);
            },
        },
    });
}

function mount(options) {
    const place = options.editor;
    const hidden = options.hidden;
    if (!place || !hidden) return null;

    activeEditor?.destroy();
    place.textContent = '';

    const labels = options.labels || {};
    const onUpdate = typeof options.onUpdate === 'function' ? options.onUpdate : () => {};
    const onSelectionUpdate = typeof options.onSelectionUpdate === 'function' ? options.onSelectionUpdate : () => {};

    const view = new EditorView(place, {
        state: EditorState.create({
            schema,
            doc: textToDoc(hidden.value, labels),
            plugins: [
                history(),
                keymap({ 'Mod-z': undo, 'Mod-y': redo, 'Mod-Shift-z': redo }),
                keymap(baseKeymap),
                placeholderPlugin(place.dataset.placeholder || 'Tulis template pesan WA...'),
            ],
        }),
        dispatchTransaction(transaction) {
            const nextState = view.state.apply(transaction);
            view.updateState(nextState);
            hidden.value = docToText(nextState.doc);
            onUpdate(hidden.value);
            onSelectionUpdate(activeMarks(view));
        },
    });

    activeEditor = {
        view,
        labels,
        destroy() {
            view.destroy();
        },
        sync() {
            hidden.value = docToText(view.state.doc);
            onUpdate(hidden.value);
            return hidden.value;
        },
        setText(text) {
            const tr = view.state.tr.replaceWith(0, view.state.doc.content.size, textToDoc(text, labels).content);
            view.dispatch(tr);
        },
        format(markName) {
            const markType = schema.marks[markName];
            if (!markType) return;
            toggleMark(markType)(view.state, view.dispatch, view);
            view.focus();
            onSelectionUpdate(activeMarks(view));
        },
        insertToken(token, label) {
            const node = schema.nodes.placeholder.create({ token, label: label || labels[token] || token });
            view.dispatch(view.state.tr.replaceSelectionWith(node, false).scrollIntoView());
            view.focus();
        },
        activeMarks() {
            return activeMarks(view);
        },
    };

    hidden.value = docToText(view.state.doc);
    onUpdate(hidden.value);
    onSelectionUpdate(activeMarks(view));
    return activeEditor;
}

window.WaTemplateEditor = {
    mount,
    sync() {
        return activeEditor?.sync() || '';
    },
    setText(text) {
        activeEditor?.setText(text);
    },
    format(markName) {
        activeEditor?.format(markName);
    },
    insertToken(token, label) {
        activeEditor?.insertToken(token, label);
    },
    activeMarks() {
        return activeEditor?.activeMarks() || {};
    },
};
