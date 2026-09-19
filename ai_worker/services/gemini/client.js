import { GoogleGenAI, ThinkingLevel } from '@google/genai';
import { config } from '../../config.js';

export function resolveThinkingLevel(configuredLevel) {
  const key = String(configuredLevel || 'LOW').toUpperCase();
  return (ThinkingLevel && ThinkingLevel[key]) ? ThinkingLevel[key] : (ThinkingLevel?.LOW || 'LOW');
}

let aiClient = null;

export function getAiClient() {
  if (!aiClient) {
    if (!config.gemini.apiKey) {
      throw new Error('GEMINI_API_KEY tidak ditemukan di environment (.env).');
    }
    aiClient = new GoogleGenAI({ apiKey: config.gemini.apiKey });
  }
  return aiClient;
}
