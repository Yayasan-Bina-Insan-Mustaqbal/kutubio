<div x-data="{
    init() {
        window.addEventListener('copy-to-clipboard', (event) => {
            const text = event.detail.text;
            navigator.clipboard.writeText(text).then(() => {
                new FilamentNotification()
                    .title('Bulk reminder list copied to clipboard')
                    .success()
                    .send();
            });
        });
    }
}"></div>
