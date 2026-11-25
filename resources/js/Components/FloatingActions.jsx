import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { usePage } from '@inertiajs/react';

const INITIAL_BOT_MESSAGE =
    "Hello! I'm Younimatiq's virtual assistant. How can I help you today?";

function ChatbotImg({ className, width, height, loading = 'lazy' }) {
    const { appUrl } = usePage().props;
    const src = `${(appUrl || '').replace(/\/$/, '')}/images/chatbot.webp`;

    return (
        <img
            loading={loading}
            src={src}
            width={width}
            height={height}
            className={className}
            alt="Chatbot"
            draggable={false}
            decoding="async"
            onError={(e) => {
                e.currentTarget.src = src;
            }}
        />
    );
}

export default function FloatingActions() {
    const { csrfToken } = usePage().props;

    const fabRootRef = useRef(null);
    const messagesEndRef = useRef(null);
    const [open, setOpen] = useState(false);
    const [chatbotOpen, setChatbotOpen] = useState(false);
    const [greenOpen, setGreenOpen] = useState(false);
    const [input, setInput] = useState('');
    const [messages, setMessages] = useState([{ role: 'bot', text: INITIAL_BOT_MESSAGE }]);
    const [sending, setSending] = useState(false);
    const [chatError, setChatError] = useState('');

    useEffect(() => {
        if (!open) return;
        const onDocumentClick = (e) => {
            if (fabRootRef.current && !fabRootRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('click', onDocumentClick);
        return () => document.removeEventListener('click', onDocumentClick);
    }, [open]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendChatbotMessage = async () => {
        const message = input.trim();
        if (!message || sending) return;

        setChatError('');
        setSending(true);
        setMessages((prev) => [...prev, { role: 'user', text: message }]);
        setInput('');

        try {
            const response = await fetch('/chatbot/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || data.error || 'Could not reach the assistant. Try again.');
            }
            setMessages((prev) => [...prev, { role: 'bot', text: data.answer || 'Sorry, I did not get a reply.' }]);
        } catch (error) {
            const msg = error?.message || 'Could not reach the assistant. Try again.';
            setChatError(msg);
            setMessages((prev) => [...prev, { role: 'bot', text: msg }]);
        } finally {
            setSending(false);
        }
    };

    const onInputKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendChatbotMessage();
        }
    };

    if (typeof document === 'undefined') return null;

    return createPortal(
        <>
            <style>{`
                @keyframes chatbot-shake-enlarge {
                    0%, 100% { transform: scale(1) rotate(0deg); }
                    10% { transform: scale(1.15) rotate(-10deg); }
                    20% { transform: scale(1.15) rotate(10deg); }
                    30% { transform: scale(1.15) rotate(-10deg); }
                    40% { transform: scale(1.15) rotate(10deg); }
                    50% { transform: scale(1) rotate(0deg); }
                }
                .chatbot-animate {
                    animation: chatbot-shake-enlarge 2s infinite ease-in-out;
                }
            `}</style>

            {/* FLOATING ACTION BUTTONS */}
            <div
                ref={fabRootRef}
                className="fixed bottom-8 right-8 z-[60] flex flex-col items-end"
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
            >
                <div
                    className={`flex flex-col-reverse items-end gap-4 mb-4 transition-all duration-300 ${
                        open
                            ? 'opacity-100 translate-y-0 pointer-events-auto'
                            : 'opacity-0 translate-y-4 pointer-events-none'
                    }`}
                >
                    <button
                        type="button"
                        className="bg-white border-2 border-secondary shadow-[3px_4px_0_theme(colors.secondary.DEFAULT)] rounded-full flex items-center hover:-translate-y-[1px] hover:shadow-[4px_5px_0_theme(colors.secondary.DEFAULT)] transition-all duration-300 h-[56px] px-5 gap-3"
                        title="Grow a greener earth"
                        onClick={() => setGreenOpen(true)}
                    >
                        <span className="text-2xl relative -top-px shrink-0">🌱</span>
                        <span className="font-bold text-secondary whitespace-nowrap pr-1">
                            Grow a greener earth
                        </span>
                    </button>

                    <a
                        aria-label="Chat on WhatsApp"
                        className="fab-3d fab-3d--whatsapp !w-[56px] !h-[56px]"
                        href="https://wa.me/12027944843"
                        rel="noopener noreferrer"
                        target="_blank"
                        title="WhatsApp"
                    >
                        <svg
                            aria-hidden="true"
                            className="w-8 h-8 relative -top-px"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                    </a>

                    <button
                        aria-label="Open chatbot"
                        type="button"
                        className="fab-3d fab-3d--chat !w-[56px] !h-[56px]"
                        title="Chatbot"
                        onClick={() => setChatbotOpen((v) => !v)}
                    >
                        <ChatbotImg
                            className="w-10 h-10 object-contain chatbot-animate"
                            width={40}
                            height={40}
                        />
                    </button>
                </div>

                <button
                    type="button"
                    className="bg-primary text-white rounded-full w-[60px] h-[60px] flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:scale-105 transition-transform"
                    aria-label="Help and Options"
                    onClick={() => setOpen((v) => !v)}
                >
                    <span
                        className={`material-symbols-outlined text-[32px] transition-transform duration-300 ${
                            open ? 'rotate-45' : ''
                        }`}
                    >
                        add_circle
                    </span>
                </button>
            </div>

            {/* Chatbot Window */}
            <div
                className={`fixed bottom-44 right-8 z-[70] w-[min(400px,calc(100vw-2rem))] bg-white rounded-2xl shadow-2xl border border-surface-container-high overflow-hidden animate-in slide-in-from-bottom-5 ${
                    chatbotOpen ? '' : 'hidden'
                }`}
            >
                <div className="bg-primary p-4 text-white flex justify-between items-center">
                    <div className="flex items-center gap-2">
                        <ChatbotImg
                            className="w-[52px] h-[52px] object-contain"
                            width={52}
                            height={52}
                        />
                        <h3 className="font-bold">Younimatiq Assistant</h3>
                    </div>
                    <button
                        aria-label="Close chatbot"
                        type="button"
                        className="hover:text-surface-container transition-colors"
                        onClick={() => setChatbotOpen(false)}
                    >
                        <span className="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div className="h-[400px] bg-surface-container-low p-4 overflow-y-auto flex flex-col gap-3">
                    {messages.map((msg, i) =>
                        msg.role === 'user' ? (
                            <div
                                key={i}
                                className="flex gap-2 max-w-[80%] self-end flex-row-reverse"
                            >
                                <div className="w-8 h-8 rounded-full bg-surface-container-high flex-shrink-0 flex items-center justify-center text-on-surface text-xs font-bold">
                                    U
                                </div>
                                <div className="bg-primary p-3 rounded-2xl rounded-tr-none shadow-sm text-sm text-white">
                                    {msg.text}
                                </div>
                            </div>
                        ) : (
                            <div key={i} className="flex gap-2 max-w-[80%]">
                                <div className="w-8 h-8 rounded-full bg-white flex-shrink-0 flex items-center justify-center overflow-hidden">
                                    <ChatbotImg
                                        className="w-6 h-6 object-contain"
                                        width={24}
                                        height={24}
                                    />
                                </div>
                                <div className="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-on-surface">
                                    {msg.text}
                                </div>
                            </div>
                        )
                    )}
                    {sending ? (
                        <div className="flex gap-2 max-w-[80%]">
                            <div className="bg-white p-3 rounded-2xl rounded-tl-none shadow-sm text-sm text-on-surface-variant italic">
                                Thinking…
                            </div>
                        </div>
                    ) : null}
                    <div ref={messagesEndRef} />
                </div>

                {chatError ? (
                    <div className="px-3 py-2 bg-error-container text-error text-xs font-medium border-t border-error/20">
                        {chatError}
                    </div>
                ) : null}

                <div className="p-3 bg-white border-t border-surface-container-high flex gap-2 items-center">
                    <input
                        className="flex-1 px-4 py-2 bg-surface-container-low rounded-full outline-none focus:ring-1 focus:ring-primary text-sm disabled:opacity-60"
                        placeholder="Type a message..."
                        type="text"
                        value={input}
                        disabled={sending}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={onInputKeyDown}
                    />
                    <button
                        aria-label="Send message"
                        type="button"
                        disabled={sending || !input.trim()}
                        className="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-transform shrink-0 disabled:opacity-60"
                        onClick={sendChatbotMessage}
                    >
                        <span className="material-symbols-outlined text-sm">{sending ? 'hourglass_empty' : 'send'}</span>
                    </button>
                </div>
            </div>

            {/* Green Modal */}
            <div
                className={`fixed inset-0 z-[70] flex items-center justify-center p-4 ${
                    greenOpen ? '' : 'hidden'
                }`}
            >
                <div
                    className="absolute inset-0 bg-on-background/40 backdrop-blur-sm"
                    onClick={() => setGreenOpen(false)}
                />
                <div className="relative bg-white rounded-2xl w-full max-w-md shadow-2xl animate-in zoom-in duration-300 overflow-hidden flex flex-col">
                    <div className="bg-[#2c772b] text-white p-4 px-6 flex justify-between items-center">
                        <h3 className="text-xl font-extrabold flex items-center gap-2">
                            🌳 Grow a Green Earth
                        </h3>
                        <button
                            type="button"
                            className="text-white hover:opacity-80 transition-opacity"
                            onClick={() => setGreenOpen(false)}
                        >
                            <span className="material-symbols-outlined font-bold text-xl">
                                close
                            </span>
                        </button>
                    </div>
                    <div className="p-6 space-y-5 text-[#2c3e2d] text-[15px] leading-relaxed">
                        <div className="bg-[#eef5ef] border-l-[3px] border-[#2c772b] p-4 rounded-r-lg">
                            <p>
                                For every 24 classes completed, we plant a tree — turning your
                                child&apos;s learning journey into real, growing impact on the
                                planet.
                            </p>
                        </div>
                        <p>
                            At Younimatiq, sustainability isn&apos;t an afterthought — it&apos;s
                            part of how we operate. We believe education and environmental
                            responsibility can grow together.
                        </p>
                        <p>
                            When your child completes their classes, they&apos;re not just learning
                            — they&apos;re contributing to a greener planet. Every milestone is a
                            tree in the ground, and every tree is a step toward a healthier Earth
                            for their generation.
                        </p>
                        <p>Thank you for being part of this journey with us.</p>
                        <div className="flex justify-end pt-2">
                            <button
                                type="button"
                                className="bg-[#2c772b] hover:bg-[#235e22] text-white font-bold py-2.5 px-8 rounded-full transition-colors"
                                onClick={() => setGreenOpen(false)}
                            >
                                Got it
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>,
        document.body
    );
}
