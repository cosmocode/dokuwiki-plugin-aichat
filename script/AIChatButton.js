class AIChatButton extends HTMLElement {
    #root = null;
    #dialog = null;


    constructor() {
        super();
        this.#root = this.attachShadow({mode: 'open'});
        this.#root.innerHTML = `
            <button class="toggle start">
                <svg viewBox="0 0 25 25"><path d="M 12.5,9.5374438e-7 A 12.496525,12.504257 0 0 0 0,12.499261 12.496525,12.504257 0 0 0 12.5,25.000001 c 2.176666,0 4.849649,-0.54718 6.756272,-1.57936 l 4.649922,1.53746 a 0.8333239,0.83383951 0 0 0 1.050432,-1.05707 l -1.543492,-4.62137 C 24.53312,17.323521 25,14.767271 25,12.499261 A 12.496525,12.504257 0 0 0 12.5,9.5374438e-7 Z M 10.792985,2.486781 c 0.707809,0 1.111255,0.7055 1.111255,1.41375 v 4.34052 h -0.895885 a 2.0022981,2.003537 0 1 0 0,1.50255 h 0.895885 v 10.91739 c 0,0.59705 -0.252193,1.19115 -0.783712,1.46263 a 3.4029055,3.405011 0 0 1 -1.56194,0.3886 c -1.511729,0 -2.635872,-0.76506 -3.359688,-1.67065 a 5.1769416,5.1801448 0 0 1 -1.02102,-2.14109 3.4509607,3.453096 0 0 1 -1.270789,-0.69139 c -0.710798,-0.61209 -1.263311,-1.59346 -1.263311,-3.04001 0,-0.75634 0.05422,-1.41552 0.190444,-1.96948 h 4.214693 c 0.678751,0 1.241472,0.50071 1.336597,1.15285 a 2.0022981,2.003537 0 1 0 1.508595,-0.007 2.8532746,2.85504 0 0 0 -2.845192,-2.64692 H 3.685239 a 2.0383394,2.0396006 0 0 1 0.495553,-0.30231 4.898622,4.901653 0 0 1 -0.178479,-1.1294 c -0.03406,-0.7363 0.07731,-1.50227 0.295637,-2.18447 0.216059,-0.67118 0.558416,-1.32566 1.038966,-1.77741 a 2.1244383,2.1257528 0 0 1 1.097296,-0.5677 c 0.199031,-0.84149 0.706823,-1.53691 1.354545,-2.0408 0.831958,-0.64915 1.922488,-1.00769 3.003729,-1.00769 z m 3.724623,0 c 1.083245,0 2.172269,0.35846 3.004228,1.00768 0.64776,0.50391 1.155325,1.20031 1.354545,2.04081 0.4205,0.07 0.794915,0.28453 1.097296,0.56819 0.48055,0.45177 0.822717,1.10473 1.038966,1.77692 0.218329,0.68219 0.328557,1.44867 0.295637,2.18496 -0.01514,0.37674 -0.07165,0.76128 -0.177981,1.12891 l 0.06631,0.0304 c 0.37044,0.17417 0.670617,0.44752 0.894887,0.81113 0.425496,0.68622 0.575818,1.67404 0.575818,2.93327 0,1.44753 -0.552973,2.43044 -1.263809,3.0405 a 3.4509607,3.453096 0 0 1 -1.271288,0.6909 5.1779427,5.1811465 0 0 1 -1.020022,2.14109 c -0.723816,0.90559 -1.848472,1.67066 -3.361185,1.67066 a 3.4029055,3.405011 0 0 1 -1.561441,-0.38762 c -0.530612,-0.27259 -0.783213,-0.86657 -0.783213,-1.46362 l -4.98e-4,-3.15375 h 1.652176 a 2.8532746,2.85504 0 0 0 2.853168,-2.85494 v -1.79786 a 2.0022981,2.003537 0 1 0 -1.501615,0 v 1.79786 a 1.3515512,1.3523875 0 0 1 -1.351553,1.3524 H 13.405855 V 3.900501 c 0,-0.70824 0.403943,-1.41375 1.111753,-1.41375 z m -5.521377,6.02664 a 0.50057453,0.50088426 0 0 1 0.655585,0.4789 0.50057453,0.50088426 0 0 1 -1.001076,0 0.50057453,0.50088426 0 0 1 0.345491,-0.4789 z m 8.010111,2.00838 a 0.50057464,0.50088437 0 0 1 0.654588,0.47391 0.50057464,0.50088437 0 1 1 -1.001077,0 0.50057464,0.50088437 0 0 1 0.346489,-0.47391 z m -7.855064,4.98205 a 0.50057453,0.50088426 0 0 1 0,1.00169 0.50057453,0.50088426 0 1 1 0,-1.00169 z" /></svg>
            </button>
            <dialog>
                <div>
                    <header>
                        <button class="fs" title="fullscreen">
                            <svg viewBox="0 0 24 24"><path d="M12 5.5L10 8H14L12 5.5M18 10V14L20.5 12L18 10M6 10L3.5 12L6 14V10M14 16H10L12 18.5L14 16M21 3H3C1.9 3 1 3.9 1 5V19C1 20.1 1.9 21 3 21H21C22.1 21 23 20.1 23 19V5C23 3.9 22.1 3 21 3M21 19H3V5H21V19Z" /></svg>
                        </button>
                        <h1>AI Chat</h1>
                        <button class="toggle" title="close">
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

        // make title attributes translatable
        for (const elem of this.#root.querySelectorAll('[title]')) {
            elem.title = this.getAttribute('title-'+elem.title) || elem.title;
        }

        this.#root.querySelector('button.start').animate({
            opacity: [0, 0.5, 1],
            transform: ['scale(0.5)', 'scale(1.1)', 'scale(1)'],
            easing: ["ease-in", "ease-out"],
        }, 1000);
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
                --color-link: #4881bf;
                --icon-size: 2em;
            }
            button {
                background: none;
                border: none;
                cursor: pointer;
            }
            :host > button svg {
                fill: var(--color-chat-icon);
                filter: drop-shadow(0.2em 0.2em 0.2em rgb(0 0 0 / 0.4));
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

                box-shadow: 0 0.2em 0.2em rgb(0 0 0 / 0.4);
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
            dialog header button svg {
                fill: var(--color-link);
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
