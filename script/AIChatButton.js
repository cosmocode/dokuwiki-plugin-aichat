class AIChatButton extends HTMLElement {
    #root = null;
    #dialog = null;


    constructor() {
        super();
        this.#root = this.attachShadow({mode: 'open'});
        this.#root.innerHTML = `
            <button>
                <svg viewBox="0 0 24 24"><path d="M12,3C17.5,3 22,6.58 22,11C22,15.42 17.5,19 12,19C10.76,19 9.57,18.82 8.47,18.5C5.55,21 2,21 2,21C4.33,18.67 4.7,17.1 4.75,16.5C3.05,15.07 2,13.13 2,11C2,6.58 6.5,3 12,3Z" /></svg>
            </button>
            <dialog>
                <div>
                    <header>
                        <button>
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

        const buttons = this.#root.querySelectorAll('button');
        buttons.forEach(function (button) {
            button.addEventListener('click', this.toggleDialog.bind(this))
        }.bind(this));
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
            dialog {
                width: 500px;
                max-width: 90vw;
                height: 800px;
                max-height: 90vh;

                position: fixed;
                top: 1em;
                right: 1em;
                left: auto;
                
                padding: 0.25em;
                
                box-shadow: 0 4px 5px rgb(0 0 0 / 30%);
                border-radius: 8px;
                border: 1px solid #fff;
            }
            dialog > div {
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            dialog header {
                text-align: right;
            }
            dialog main {
                overflow: auto;
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
