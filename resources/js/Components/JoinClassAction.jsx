import { useState } from 'react';

export default function JoinClassAction({ joinUrl, passcode, buttonClass = 'btn-primary btn-sm !bg-success hover:!bg-success' }) {
    const [opened, setOpened] = useState(false);
    if (!joinUrl) return null;

    return (
        <div className="flex flex-col gap-1">
            <a
                href={joinUrl}
                target="_blank"
                rel="noreferrer"
                className={buttonClass}
                onClick={() => setOpened(true)}
            >
                {opened ? 'Opening class…' : 'Join Class'}
            </a>
            {passcode ? (
                <div className="text-[10px] text-on-surface-variant font-bold bg-surface-container-low px-2 py-0.5 rounded border border-outline-variant mt-1">Passcode: {passcode}</div>
            ) : null}
            {opened ? (
                <span className="text-[10px] text-on-surface-variant">If nothing opened, allow pop-ups and try again.</span>
            ) : null}
        </div>
    );
}
