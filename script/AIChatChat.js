class AIChatChat extends HTMLElement {
    #root = null;
    #controls = null;
    #input = null;
    #output = null;
    #progress = null;
    #pagecontext = null;
    #history = [];

    constructor() {
        super();

        this.#root = this.attachShadow({mode: 'open'});
        this.#root.innerHTML = `
            <div class="output"></div>
            <form>
                <progress max="100" value="0"></progress>
                <div class="controls">
                    <div class="col">
                        <button type="button" class="pagecontext toggle off" title="pagecontext">
                            <svg viewBox="0 0 24 24"><path d="M6,2A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6M6,4H13V9H18V20H6V4M8,12V14H16V12H8M8,16V18H13V16H8Z" /></svg>
                        </button>
                    </div>

                    <textarea autofocus></textarea>

                    <div class="col">
                        <button type="button" class="delete-history" title="restart">
                            <svg viewBox="0 0 24 24"><path d="M12,4C14.1,4 16.1,4.8 17.6,6.3C20.7,9.4 20.7,14.5 17.6,17.6C15.8,19.5 13.3,20.2 10.9,19.9L11.4,17.9C13.1,18.1 14.9,17.5 16.2,16.2C18.5,13.9 18.5,10.1 16.2,7.7C15.1,6.6 13.5,6 12,6V10.6L7,5.6L12,0.6V4M6.3,17.6C3.7,15 3.3,11 5.1,7.9L6.6,9.4C5.5,11.6 5.9,14.4 7.8,16.2C8.3,16.7 8.9,17.1 9.6,17.4L9,19.4C8,19 7.1,18.4 6.3,17.6Z" /></svg>
                        </button>
                        <button type="submit" class="send" title="send">
                            <svg viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
                        </button>
                    </div>
                </div>
            </form>
        `;
        this.#root.appendChild(this.getStyle());
        this.#input = this.#root.querySelector('textarea');
        this.#output = this.#root.querySelector('.output');
        this.#progress = this.#root.querySelector('progress');
        this.#controls = this.#root.querySelector('.controls');
        this.#pagecontext = this.#root.querySelector('.pagecontext');
        const form = this.#root.querySelector('form');
        form.addEventListener('submit', this.onSubmit.bind(this));
        const restart = this.#root.querySelector('.delete-history');
        restart.addEventListener('click', this.deleteHistory.bind(this));
        this.#input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey) {
                event.preventDefault();
                this.onSubmit(event);
            }
        });
        this.#pagecontext.addEventListener('click', (event) => {
            this.#pagecontext.classList.toggle('off');
        });
    }

    /**
     * Called when the DOM has been connected
     *
     * We initialize the attribute based states here
     */
    connectedCallback() {
        this.#input.placeholder = this.getAttribute('placeholder') || 'Your question...';
        this.displayMessage(this.getAttribute('hello') || 'Hello, how can I help you?', {});

        // make title attributes translatable
        for (const elem of this.#root.querySelectorAll('[title]')) {
            elem.title = this.getAttribute('title-'+elem.title) || elem.title;
        }

        this.restoreHistory();
        this.stopProgress(); // initializes the visibility states
    }

    /**
     * Define the web component's internal styles
     *
     * @returns {HTMLStyleElement}
     */
    getStyle() {
        const style = document.createElement('style');
        style.textContent = `
            :host {
                --color-human: #ebd8b2;
                --color-ai: #c6dbf2;
                --color-link: #4881bf;

                display: flex;
                flex-direction: column;
                height: 100%;
                justify-content: space-between;
            }

            * {
                box-sizing: border-box;
                font-family: sans-serif;
            }
            form {
                clear: both;

            }
            .controls {
                width: 100%;
                display: flex;
                flex-direction: row;
                align-items: flex-end;
                gap: 0.25em;
            }

            .controls .col {
                display: flex;
                flex-direction: column;
                gap: 0.25em;
            }

            .controls button {
                padding: 0;
                background: none;
                border: none;
                cursor: pointer;
                display: flex;
                width: 2.5em;
            }
            .controls button svg {
                flex-grow: 1;
                flex-shrink: 1;
                fill: var(--color-link);
            }

            .controls button.toggle.off svg {
                fill: #ccc;
            }

            .controls textarea {
                width: 100%;
                padding: 0.25em;
                font-size: 1.2em;
                height: 5em;
                border: none;
                resize: vertical;
            }
            .controls textarea:focus {
                outline: none;
            }
            progress{
                width: 100%;
            }
            progress {
                color: var(--color-link);
                accent-color: var(--color-link);
            }
            a {
                color: var(--color-link);
            }
            .output > div {
                border-radius: 0.25em;
                clear: both;
                padding: 0.5em 1em;
                position: relative;
                margin-bottom: 1em;
                max-width: calc(100% - 4em);
            }
            .output > div::before {
                content: "";
                width: 0px;
                height: 0px;
                position: absolute;
                top: 0;
            }
            .output > div.user {
                background-color: var(--color-human);
                float: right;
                margin-right: 2em;
                border-top-right-radius: 0;
            }
            .output > div.user::before {
                right: -1em;
                border-left: 0.5em solid var(--color-human);
                border-right: 0.5em solid transparent;
                border-top: 0.5em solid var(--color-human);
                border-bottom: 0.5em solid transparent;
            }
            .output > div.ai {
                background-color: var(--color-ai);
                float: left;
                margin-left: 2em;
                border-top-left-radius: 0;
            }
            .output > div.ai::before {
                left: -1em;
                border-left: 0.5em solid transparent;
                border-right: 0.5em solid var(--color-ai);
                border-top: 0.5em solid var(--color-ai);
                border-bottom: 0.5em solid transparent;
            }
            .output pre {
                max-width: 100%;
                overflow: auto;
                scrollbar-width: thin;
            }
            .output > div.ai pre {
                scrollbar-color: var(--color-link) var(--color-ai);
            }
            .output > div.human pre {
                scrollbar-color: var(--color-link) var(--color-human);
            }
        `;
        return style;
    }

    /**
     * Save history to session storage
     */
    saveHistory() {
        sessionStorage.setItem('ai-chat-history', JSON.stringify(this.#history));
    }

    /**
     * Load the history from session storage and display it
     */
    restoreHistory() {
        const history = sessionStorage.getItem('ai-chat-history');
        if (history) {
            this.#history = JSON.parse(history);
            this.#history.forEach(row => {
                this.displayMessage(row[0]);
                this.displayMessage(row[1], row[2]);
            });
        }
    }

    /**
     * Clear the history and reset the chat
     */
    deleteHistory() {
        sessionStorage.removeItem('ai-chat-history');
        this.#history = [];
        this.#output.innerHTML = '';
        this.connectedCallback(); // re-initialize
    }

    /**
     * Get the current page context if enabled, empty string otherwise
     *
     * @returns {string}
     */
    getPageContext() {
        return this.#pagecontext.classList.contains('off') ? '' : JSINFO.id;
    }

    /**
     * Submit the given question to the server
     *
     * @param event
     * @returns {Promise<void>}
     */
    async onSubmit(event) {
        event.preventDefault();
        const message = this.#input.value;
        if (!message) return;

        // show original question for now
        const p = this.displayMessage(message);

        this.#input.value = '';
        this.startProgress();
        try {
            const response = await this.sendMessage(message, this.getPageContext());
            this.#history.push([response.question, response.answer, response.sources]);
            this.saveHistory();
            p.textContent = response.question; // replace original question with interpretation
            p.title = message; // show original question on hover
            this.displayMessage(response.answer, response.sources); // display the answer
        } catch (e) {
            console.error(e);
            this.displayMessage(LANG.plugins.aichat.error, {});
        }

        this.stopProgress();
        this.#input.focus();
        p.scrollIntoView();
    }

    /**
     * Called when waiting for the response has started
     *
     * Hides the input field and shows the progress bar
     */
    startProgress() {
        this.#controls.style.display = 'none';
        this.#progress.style.display = 'block';
        this.#progress.value = 0;

        this.#progress._timer = setInterval(() => {
            this.#progress.scrollIntoView();
            const missing = this.#progress.max - this.#progress.value;
            const add = missing / 100; // we will never reach 100%
            this.#progress.value += add;
        }, 100);
    }

    /**
     * Called when waiting for the response has finished
     *
     * Resets the progress bar and shows the input field again
     */
    stopProgress() {
        if (this.#progress._timer) {
            clearInterval(this.#progress._timer);
            this.#progress._timer = null;
        }
        this.#controls.style.removeProperty('display');
        this.#progress.style.display = 'none';
    }

    /**
     * Display a message in the chat
     *
     * @param {string} message
     * @param {object|null} sources Dict of sources {url:title, ...}  if given this is assumed to be an AI message
     * @returns {HTMLParagraphElement} Reference to the newly added message
     */
    displayMessage(message, sources = null) {
        const div = document.createElement('div');
        if(sources !== null) {
            div.classList.add('ai');
            div.innerHTML = message; // we get HTML for AI messages
        } else {
            div.classList.add('user');
            div.textContent = message;
        }

        if (sources !== null && sources.length > 0) {
            const ul = document.createElement('ul');
            sources.forEach((source) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = source.url;
                a.textContent = source.title;
                a.title = `${source.page} (${source.score})`;
                li.appendChild(a);
                ul.appendChild(li);
            });
            div.appendChild(ul);
        }

        this.#output.appendChild(div);
        return div;
    }

    /**
     * Send a question to the server
     *
     * @param {string} message
     * @param {string} pageContext The current page ID if it should be used as context
     * @returns {Promise<object>}
     */
    async sendMessage(message, pageContext = '') {
        const formData = new FormData();
        formData.append('question', message);
        formData.append('history', JSON.stringify(this.#history));
        formData.append('pagecontext', pageContext);

        const response = await fetch(this.getAttribute('url') || '/', {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }
}

window.customElements.define('aichat-chat', AIChatChat);
