export function registerChatWidget(Alpine) {
    Alpine.data('chatWidget', () => ({
        open: false,
        loading: false,
        listening: false,
        speechEnabled: false,
        draft: '',
        messages: [],
        recognition: null,

        init() {
            const dataEl = document.getElementById('chat-history');

            if (dataEl) {
                try {
                    this.messages = JSON.parse(dataEl.textContent);
                } catch (e) {
                    this.messages = [];
                }
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (SpeechRecognition) {
                // continuous + interimResults: without these, Chrome's voice-activity
                // detector finalizes the result after the first short pause — often
                // right after a single word — which is why voice used to cut off early.
                // Keeping the mic open and accumulating "final" chunks until the user
                // stops it (or a longer natural silence ends the session) lets a full
                // sentence like "agrega un gasto de 25 soles en transporte" come through.
                this.recognition = new SpeechRecognition();
                this.recognition.lang = 'es-PE';
                this.recognition.continuous = true;
                this.recognition.interimResults = true;
                this.recognition.maxAlternatives = 1;

                this.finalTranscript = '';
                this.silenceTimer = null;

                // continuous:true keeps the mic open indefinitely — Chrome no
                // longer ends the session on its own after a short pause, so
                // without this timer the user would have to click the mic
                // button every single time. Resetting it on every result and
                // firing after ~1.5s of silence gives the "stops talking for
                // a second or two → sends automatically" behavior.
                this.resetSilenceTimer = () => {
                    clearTimeout(this.silenceTimer);
                    this.silenceTimer = setTimeout(() => {
                        if (this.listening) {
                            this.recognition.stop();
                        }
                    }, 1500);
                };

                this.recognition.onresult = (event) => {
                    let interim = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;

                        if (event.results[i].isFinal) {
                            this.finalTranscript += transcript;
                        } else {
                            interim += transcript;
                        }
                    }

                    this.draft = (this.finalTranscript + interim).trim();
                    this.resetSilenceTimer();
                };
                this.recognition.onerror = () => {
                    this.listening = false;
                    clearTimeout(this.silenceTimer);
                };
                this.recognition.onend = () => {
                    this.listening = false;
                    clearTimeout(this.silenceTimer);

                    // Covers both ways a session can end: natural silence
                    // (via the timer above calling stop()) and the user
                    // manually clicking the mic button to pause it early —
                    // either way, whatever was captured gets sent.
                    const message = this.finalTranscript.trim() || this.draft.trim();
                    this.finalTranscript = '';

                    if (message) {
                        this.draft = message;
                        this.send();
                    }
                };
            }

            this.$nextTick(() => this.scrollToBottom());
        },

        toggleListening() {
            if (!this.recognition) {
                alert('Tu navegador no soporta reconocimiento de voz, o la página necesita abrirse con HTTPS.');

                return;
            }

            if (this.listening) {
                this.recognition.stop();

                return;
            }

            clearTimeout(this.silenceTimer);
            this.finalTranscript = '';
            this.draft = '';
            this.listening = true;
            this.recognition.start();
        },

        speak(text) {
            if (!this.speechEnabled || !window.speechSynthesis) {
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'es-PE';
            window.speechSynthesis.speak(utterance);
        },

        scrollToBottom() {
            this.$nextTick(() => {
                if (this.$refs.scrollArea) {
                    this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight;
                }
            });
        },

        async send() {
            const message = this.draft.trim();

            if (!message || this.loading) {
                return;
            }

            this.messages.push({ role: 'user', content: message });
            this.draft = '';
            this.loading = true;
            this.scrollToBottom();

            try {
                const response = await fetch(window.chatSendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ message }),
                });

                const data = await response.json();
                const reply = data.reply || 'Hubo un problema, intenta de nuevo.';

                this.messages.push({ role: 'assistant', content: reply });
                this.speak(reply);
            } catch (e) {
                this.messages.push({ role: 'assistant', content: 'No pude conectarme. Intenta de nuevo.' });
            } finally {
                this.loading = false;
                this.scrollToBottom();
            }
        },
    }));
}
