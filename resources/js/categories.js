import { initializeCatalog, initializeEditor } from './catalog';
initializeCatalog('categories');
initializeEditor({ dialogId: 'category-editor', formId: 'category-form', createTitle: 'A new place to belong', editTitle: 'Edit category', fields: { name: '', description: '', discount_percent: '0' } });
