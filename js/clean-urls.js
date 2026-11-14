(function () {
    'use strict';

    function cleanUrl(value) {
        if (!value || value.charAt(0) === '#' || /^(?:mailto:|tel:|javascript:)/i.test(value))
            return value;
        if (/^(?:https?:)?\/\//i.test(value)) {
            try {
                var url = new URL(value, window.location.href);
                if (url.origin !== window.location.origin)
                    return value;
            } catch (e) {
                return value;
            }
        }
        return value.replace(/\.php(?=([?#]|$))/i, '');
    }

    function normalize(root) {
        var nodes = (root || document).querySelectorAll('a[href], form[action]');
        Array.prototype.forEach.call(nodes, function (node) {
            var attr = node.tagName === 'FORM' ? 'action' : 'href';
            var value = node.getAttribute(attr);
            var clean = cleanUrl(value);
            if (clean !== value)
                node.setAttribute(attr, clean);
        });
    }

    function start() {
        normalize(document);
        if (window.MutationObserver)
            new MutationObserver(function (changes) {
                changes.forEach(function (change) {
                    Array.prototype.forEach.call(change.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            if (node.matches && node.matches('a[href], form[action]'))
                                normalize(node.parentNode);
                            else
                                normalize(node);
                        }
                    });
                });
            }).observe(document.body, {childList: true, subtree: true});
    }

    if (document.readyState === 'loading')
        document.addEventListener('DOMContentLoaded', start);
    else
        start();
}());
