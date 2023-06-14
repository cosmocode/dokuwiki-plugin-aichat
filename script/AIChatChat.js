class AIChatChat extends HTMLElement {
    #root = null;
    #input = null;
    #output = null;
    #progress = null;
    #history = [];

    constructor() {
        super();

        this.#root = this.attachShadow({mode: 'open'});
        this.#root.innerHTML = `
            <div class="output"></div>
            <form>
                <progress max="100" value="0"></progress>
                <input type="text" autofocus />
            </form>
        `;
        this.#root.appendChild(this.getStyle());
        this.#input = this.#root.querySelector('input');
        this.#output = this.#root.querySelector('.output');
        this.#progress = this.#root.querySelector('progress');
        const form = this.#root.querySelector('form');
        form.addEventListener('submit', this.onSubmit.bind(this));
    }

    static get observedAttributes() {
        return [
            'placeholder', // placeholder for the input field
            'hello', // initial fake message by the AI
            'url', // URL to the AI server endpoint
        ];
    }

    /**
     * Called when the DOM has been connected
     *
     * We initialize the attribute based states here
     */
    connectedCallback() {
        this.#input.placeholder = this.getAttribute('placeholder') || 'Your question...';
        this.displayMessage(this.getAttribute('hello') || 'Hello, how can I help you?', {});
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
                margin-bottom: 1em;
            }
            progress,
            input {
                width: 100%;
            }
            input {
                padding: 0.25em;
                font-size: 1.2em;  
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
            const response = await this.sendMessage(message);
            this.#history.push([response.question, response.answer, response.sources]);
            this.saveHistory();
            p.textContent = response.question; // replace original question with interpretation
            p.title = message; // show original question on hover
            this.displayMessage(response.answer, response.sources); // display the answer
        } catch (e) {
            console.error(e);
            this.displayMessage('Sorry, something went wrong', {});
        }

        this.stopProgress();
        this.#input.focus();
    }

    /**
     * Called when waiting for the response has started
     *
     * Hides the input field and shows the progress bar
     */
    startProgress() {
        this.#input.style.display = 'none';
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
        this.#input.style.display = 'initial';
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

        if (sources !== null && Object.keys(sources).length > 0) {
            const ul = document.createElement('ul');
            Object.entries(sources).forEach(([url, title]) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = url;
                a.textContent = title;
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
     * @returns {Promise<object>}
     */
    async sendMessage(message) {
        const formData = new FormData();
        formData.append('question', message);
        formData.append('history', JSON.stringify(this.#history));

        const response = await fetch(this.getAttribute('url') || '/', {
            method: 'POST',
            body: formData
        });

        return await response.json();
    }
}

window.customElements.define('aichat-chat', AIChatChat);
