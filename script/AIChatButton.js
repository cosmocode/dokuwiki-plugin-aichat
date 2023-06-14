class AIChatButton extends HTMLElement {
    #root = null;
    #dialog = null;


    constructor() {
        super();
        this.#root = this.attachShadow({mode: 'open'});
        this.#root.innerHTML = `
            <button class="toggle start">
                <svg viewBox="0 0 24 24"><path d="M12,3C17.5,3 22,6.58 22,11C22,15.42 17.5,19 12,19C10.76,19 9.57,18.82 8.47,18.5C5.55,21 2,21 2,21C4.33,18.67 4.7,17.1 4.75,16.5C3.05,15.07 2,13.13 2,11C2,6.58 6.5,3 12,3Z" /></svg>
            </button>
            <dialog>
                <div>
                    <header>
                        <button class="fs" title="Fullscreen">
                            <svg viewBox="0 0 24 24"><path d="M12 5.5L10 8H14L12 5.5M18 10V14L20.5 12L18 10M6 10L3.5 12L6 14V10M14 16H10L12 18.5L14 16M21 3H3C1.9 3 1 3.9 1 5V19C1 20.1 1.9 21 3 21H21C22.1 21 23 20.1 23 19V5C23 3.9 22.1 3 21 3M21 19H3V5H21V19Z" /></svg>
                        </button>
                        <h1>AI Chat</h1>
                        <button class="toggle" title="Close">
                            <svg viewBox="0 0 24 24"><path d="M13.46,12L19,17.54V19H17.54L12,13.46L6.46,19H5V17.54L10.54,12L5,6.46V5H6.46L12,10.54L17.54,5H19V6.46L13.46,12Z" /></svg>
                        </button>
                    </header>
                    <main>
                        <slot></slot>
                    </main>
                </div>
            </dialog>
        `;

        this.#root.appendChild(this.getStyle());
        this.#dialog = this.#root.querySelector('dialog');

        const toggleButtons = this.#root.querySelectorAll('button.toggle');
        toggleButtons.forEach(function (button) {
            button.addEventListener('click', this.toggleDialog.bind(this))
        }.bind(this));

        this.#dialog.querySelector('button.fs').addEventListener('click', function() {
            this.#dialog.classList.toggle('fullscreen');
        }.bind(this));
    }

    /**
     * Called when the DOM has been connected
     *
     * We initialize the attribute based states here
     */
    connectedCallback() {
        this.#root.querySelector('button.start').title = this.getAttribute('label') || 'AI Chat';
        this.#dialog.querySelector('header h1').textContent = this.getAttribute('label') || 'AI Chat';
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
                --color-chat-icon: #4881bf;
                --icon-size: 2em;
            }
            button {
                background: none;
                border: none;
                cursor: pointer;
            }
            :host > button svg {
                fill: var(--color-chat-icon);
            }
            svg {
                width: 2em;
                height: 2em;
            }
            button.start svg {
                width: var(--icon-size);
                height: var(--icon-size);
            }
            dialog {
                width: 500px;
                max-width: 90vw;
                height: 800px;
                max-height: 90vh;

                position: fixed;
                top: 1em;
                right: 1em;
                left: auto;
                z-index: 9999;
                
                padding: 0.5em;
                
                box-shadow: 0 4px 5px rgb(0 0 0 / 30%);
                border-radius: 8px;
                border: 1px solid #fff;
            }
            dialog.fullscreen {
                width: 100%;
                height: 100%;
                left: 1em;
                right: 1em;
                
            }
            dialog > div {
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            dialog header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
            }
            dialog main {
                overflow: auto;
                flex-grow: 1;
            }
        `;
        return style;
    }

    toggleDialog() {
        if (this.#dialog.open) {
            this.#dialog.close();
        } else {
            this.#dialog.show();
        }
    }
}

window.customElements.define('aichat-button', AIChatButton);
