/**
 * Polls a live-attention support thread every ~5s from the admin side, so
 * the student's new messages show up without the admin needing to submit
 * their own reply first (that already refreshes the whole page/thread via
 * the normal form redirect — this only covers sitting idle waiting).
 */
export function registerAdminSupportPoll(Alpine) {
    Alpine.data('liveSupportThread', (lastMessageId, url) => ({
        lastMessageId,
        incoming: [],

        init() {
            const intervalId = setInterval(() => this.poll(), 5000);

            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll() {
            try {
                const response = await fetch(`${url}?after_id=${this.lastMessageId}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                data.messages.forEach((msg) => {
                    this.incoming.push(msg);
                    this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                });
            } catch (e) {
                // A failed poll just tries again on the next interval tick.
            }
        },
    }));
}
