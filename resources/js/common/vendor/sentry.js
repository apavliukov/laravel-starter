import * as Sentry from '@sentry/browser';

const startSentryBrowser = ({ scope: appScope }) => {
    if (import.meta.env.VITE_APP_ENV !== 'production') {
        return;
    }

    const dsn = import.meta.env.VITE_SENTRY_JAVASCRIPT_DSN ?? null;

    if (!dsn) {
        return;
    }

    Sentry.onLoad(function () {
        Sentry.init({
            dsn,
            tracesSampleRate: 1.0,
            sendDefaultPii: true,
        });
        Sentry.getCurrentScope().setTag('app-scope', appScope);
    });
};

export default startSentryBrowser;
