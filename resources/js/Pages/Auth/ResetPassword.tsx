import AuthForm from './AuthForm';
export default function ResetPassword(props: { email: string; token: string }) { return <AuthForm mode="reset" {...props} />; }
