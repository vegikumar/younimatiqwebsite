export default function AssignmentSubmissionAnswers({ submission }) {
    const questions = Object.fromEntries((submission.questions || []).map((q) => [q.id, q]));
    const answers = submission.answers || {};

    return (
        <div className="mt-3 space-y-4">
            {submission.content ? (
                <div className="p-3 bg-white rounded-lg border border-outline-variant/30">
                    <p className="text-xs font-bold text-on-surface-variant uppercase mb-2">Submitted text / link</p>
                    <p className="text-sm whitespace-pre-wrap">{submission.content}</p>
                </div>
            ) : null}
            {submission.fileUrl ? (
                <div className="p-3 bg-white rounded-lg border border-outline-variant/30">
                    <p className="text-xs font-bold text-on-surface-variant uppercase mb-2">Submitted file</p>
                    <a href={submission.fileUrl} target="_blank" rel="noreferrer" className="text-primary text-sm font-bold hover:underline">
                        View attachment
                    </a>
                </div>
            ) : null}
            {Object.keys(answers).map((qId) => {
                const q = questions[qId];
                const ans = answers[qId];
                if (!ans) return null;
                return (
                    <div key={qId} className="p-3 bg-white rounded-lg border border-outline-variant/30">
                        <p className="text-xs font-bold text-on-surface-variant uppercase mb-1">{q?.question ?? 'Question'}</p>
                        <p className="text-sm text-on-surface">{typeof ans === 'object' ? JSON.stringify(ans) : String(ans)}</p>
                    </div>
                );
            })}
        </div>
    );
}
