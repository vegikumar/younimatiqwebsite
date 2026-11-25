export default function SubmitButton({
    processing = false,
    disabled = false,
    children = 'Save',
    processingLabel = 'Saving…',
    className = 'btn-primary',
    type = 'submit',
    ...rest
}) {
    const isDisabled = disabled || processing;
    return (
        <button type={type} disabled={isDisabled} className={`${className} disabled:opacity-60`} {...rest}>
            {processing ? processingLabel : children}
        </button>
    );
}
