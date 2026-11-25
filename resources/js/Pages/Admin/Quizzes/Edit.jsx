import QuizForm from './QuizForm';

export default function Edit({ quiz, ...rest }) {
    return <QuizForm mode="edit" quiz={quiz} {...rest} />;
}
