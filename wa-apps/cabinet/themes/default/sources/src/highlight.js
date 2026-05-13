const hljs = require('highlight.js/lib/core');

hljs.registerLanguage('css', require('highlight.js/lib/languages/css'));
hljs.registerLanguage('xml', require('highlight.js/lib/languages/xml'));
hljs.registerLanguage('typescript', require('highlight.js/lib/languages/typescript'));
hljs.registerLanguage('json', require('highlight.js/lib/languages/json'));
hljs.highlightAll();
