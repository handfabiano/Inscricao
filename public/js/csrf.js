/**
 * CSRF Protection for AJAX Requests
 *
 * Automatically adds CSRF token to all AJAX requests
 *
 * @version 1.0
 * @date 2025-11-08
 */

(function() {
    'use strict';

    /**
     * Obtém token CSRF da meta tag
     */
    function getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }

    /**
     * Adiciona token CSRF aos headers do fetch
     */
    function addCSRFToHeaders(headers) {
        const token = getCSRFToken();
        if (token) {
            headers = headers || {};
            headers['X-CSRF-Token'] = token;
        }
        return headers;
    }

    /**
     * Wrapper para fetch com CSRF automático
     */
    window.fetchWithCSRF = function(url, options = {}) {
        options.headers = addCSRFToHeaders(options.headers);
        return fetch(url, options);
    };

    /**
     * Adiciona CSRF a todos os formulários
     */
    function addCSRFToForms() {
        const token = getCSRFToken();
        if (!token) return;

        document.querySelectorAll('form').forEach(form => {
            // Verificar se já tem campo CSRF
            if (form.querySelector('input[name="csrf_token"]')) {
                return;
            }

            // Adicionar apenas a métodos que precisam (POST, PUT, DELETE)
            const method = (form.method || 'GET').toUpperCase();
            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = token;
                form.appendChild(input);
            }
        });
    }

    /**
     * Configurar jQuery AJAX (se disponível)
     */
    if (typeof jQuery !== 'undefined') {
        jQuery.ajaxSetup({
            beforeSend: function(xhr) {
                const token = getCSRFToken();
                if (token) {
                    xhr.setRequestHeader('X-CSRF-Token', token);
                }
            }
        });
    }

    /**
     * Configurar XMLHttpRequest global
     */
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        this._url = url;
        this._method = method;
        return originalOpen.apply(this, [method, url, ...args]);
    };

    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(...args) {
        const protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (protectedMethods.includes(this._method?.toUpperCase())) {
            const token = getCSRFToken();
            if (token) {
                this.setRequestHeader('X-CSRF-Token', token);
            }
        }
        return originalSend.apply(this, args);
    };

    // Executar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addCSRFToForms);
    } else {
        addCSRFToForms();
    }

    // Adicionar CSRF a formulários criados dinamicamente
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeName === 'FORM') {
                    addCSRFToForms();
                } else if (node.querySelectorAll) {
                    if (node.querySelectorAll('form').length > 0) {
                        addCSRFToForms();
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

})();

/**
 * Exemplo de uso:
 *
 * // Fetch com CSRF automático
 * fetchWithCSRF('/api/endpoint', {
 *     method: 'POST',
 *     body: JSON.stringify({data: 'value'}),
 *     headers: {'Content-Type': 'application/json'}
 * }).then(response => response.json());
 *
 * // jQuery AJAX (CSRF adicionado automaticamente)
 * $.post('/api/endpoint', {data: 'value'});
 *
 * // XMLHttpRequest (CSRF adicionado automaticamente)
 * const xhr = new XMLHttpRequest();
 * xhr.open('POST', '/api/endpoint');
 * xhr.send(formData);
 *
 * // Formulários HTML (CSRF adicionado automaticamente)
 * <form method="POST" action="/submit">
 *     <!-- Campo csrf_token será adicionado automaticamente -->
 * </form>
 */
