import { initializeCatalog, initializeEditor } from './catalog';
initializeCatalog('levels');
initializeEditor({ dialogId: 'level-editor', formId: 'level-form', createTitle: 'A new level of possibility', editTitle: 'Edit membership level', fields: { name: '', fee: '', validity_days: '365', benefits: '' } });
