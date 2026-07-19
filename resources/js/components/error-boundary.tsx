import { Component, type ReactNode } from 'react';

/**
 * App-wide safety net. With SSR off, an uncaught render error or a stale asset
 * after a deploy would otherwise leave a blank white page. This catches render
 * errors and shows a recoverable message; stale-asset (chunk-load) errors are
 * auto-recovered with a single guarded reload to fetch the fresh build.
 */

const RELOAD_KEY = 'vn_last_chunk_reload';

function isChunkLoadError(error: unknown): boolean {
    const msg = error instanceof Error ? error.message : String(error ?? '');
    return /loading chunk|dynamically imported module|importing a module script failed|failed to fetch dynamically|error loading/i.test(
        msg,
    );
}

/** Reload once to pick up new assets, guarded against reload loops. */
export function reloadForStaleAssets(): boolean {
    try {
        const last = Number(sessionStorage.getItem(RELOAD_KEY) || 0);
        if (Date.now() - last > 10_000) {
            sessionStorage.setItem(RELOAD_KEY, String(Date.now()));
            window.location.reload();
            return true;
        }
    } catch {
        // sessionStorage can be unavailable (private mode) — fall through.
    }
    return false;
}

type Props = { children: ReactNode };
type State = { hasError: boolean };

export class ErrorBoundary extends Component<Props, State> {
    state: State = { hasError: false };

    static getDerivedStateFromError(): State {
        return { hasError: true };
    }

    componentDidCatch(error: unknown) {
        if (isChunkLoadError(error) && reloadForStaleAssets()) {
            return; // a reload is in flight; don't bother rendering the fallback
        }
    }

    render() {
        if (this.state.hasError) {
            return <Fallback />;
        }
        return this.props.children;
    }
}

function Fallback() {
    return (
        <div
            style={{
                minHeight: '100vh',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '14px',
                padding: '24px',
                textAlign: 'center',
                background: '#f1f0ea',
                color: '#0f1c17',
                fontFamily: 'Georgia, "Times New Roman", serif',
            }}
        >
            <h1 style={{ fontSize: '26px', fontWeight: 400, margin: 0 }}>Something went wrong</h1>
            <p style={{ maxWidth: '420px', fontSize: '15px', lineHeight: 1.6, color: '#4b5850', fontFamily: 'system-ui, sans-serif' }}>
                We hit a snag loading this page. Reloading usually fixes it — if it
                keeps happening, sign out and back in.
            </p>
            <div style={{ display: 'flex', gap: '10px', marginTop: '6px', flexWrap: 'wrap', justifyContent: 'center' }}>
                <button
                    type="button"
                    onClick={() => window.location.reload()}
                    style={{
                        background: '#0f1c17',
                        color: '#f1f0ea',
                        border: 'none',
                        padding: '11px 22px',
                        fontSize: '12px',
                        letterSpacing: '0.12em',
                        textTransform: 'uppercase',
                        cursor: 'pointer',
                        fontFamily: 'system-ui, sans-serif',
                    }}
                >
                    Reload page
                </button>
                <a
                    href="/dashboard"
                    style={{
                        border: '1px solid #0f1c17',
                        color: '#0f1c17',
                        padding: '11px 22px',
                        fontSize: '12px',
                        letterSpacing: '0.12em',
                        textTransform: 'uppercase',
                        textDecoration: 'none',
                        fontFamily: 'system-ui, sans-serif',
                    }}
                >
                    Go to dashboard
                </a>
            </div>
        </div>
    );
}
