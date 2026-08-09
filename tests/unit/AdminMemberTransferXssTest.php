<?php

use CodeIgniter\Test\CIUnitTestCase;

final class AdminMemberTransferXssTest extends CIUnitTestCase
{
    public function testMemberTransferRendersStoredValuesAsTextInsteadOfHtml(): void
    {
        $source = file_get_contents(FCPATH . 'assets/js/admin/pages.js');

        $this->assertIsString($source);

        $blockStart = strpos($source, 'checked.forEach(function(cb)');
        $blockEnd = strpos($source, 'targetList.appendChild(el);', $blockStart);

        $this->assertNotFalse($blockStart);
        $this->assertNotFalse($blockEnd);

        $memberTransferBlock = substr($source, $blockStart, $blockEnd - $blockStart);

        $this->assertStringNotContainsString('innerHTML', $memberTransferBlock);
        $this->assertStringContainsString('memberName.textContent = name;', $memberTransferBlock);
        $this->assertStringContainsString('memberDetail.textContent = detail;', $memberTransferBlock);
        $this->assertStringContainsString('avatar.textContent = initial;', $memberTransferBlock);
    }
}
