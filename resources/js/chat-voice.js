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
                this.recognition = new SpeechRecognition();
                this.recognition.lang = 'es-PE';
                this.recognition.interimResults = false;
                this.recognition.maxAlternatives = 1;

                this.recognition.onresult = (event) => {
                    this.draft = event.results[0][0].transcript;
                    this.listening = false;
                    this.send();
                };
                this.recognition.onerror = () => {
                    this.listening = false;
                };
                this.recognition.onend = () => {
                    this.listening = false;
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
                this.listening = false;

                return;
            }

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
