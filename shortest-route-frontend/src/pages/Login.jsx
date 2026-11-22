import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../AuthContext.jsx';
import { errorMessage } from '../api';
import Field from '../components/Field.jsx';

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();

    const clientErrors = {};
    if (!form.email.trim()) clientErrors.email = 'Email is required';
    if (!form.password) clientErrors.password = 'Password is required';
    setErrors(clientErrors);
    if (Object.keys(clientErrors).length) return;

    setLoading(true);
    try {
      await login(form.email, form.password);
      toast.success('Welcome back!');
      navigate('/');
    } catch (err) {
      setErrors(err.response?.data?.errors ? flatten(err.response.data.errors) : {});
      toast.error(errorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-md rounded-2xl bg-white p-6 shadow">
      <h1 className="mb-4 text-2xl font-semibold">Log in</h1>
      <form onSubmit={submit} className="space-y-4" noValidate>
        <Field label="Email" type="email" value={form.email} onChange={set('email')} error={errors.email} autoComplete="email" />
        <Field label="Password" type="password" value={form.password} onChange={set('password')} error={errors.password} autoComplete="current-password" />
        <button disabled={loading} className="w-full rounded-lg bg-slate-900 py-2.5 font-medium text-white hover:bg-slate-800 disabled:opacity-60">
          {loading ? 'Signing in…' : 'Log in'}
        </button>
      </form>
      <p className="mt-4 text-sm text-slate-600">
        No account? <Link to="/register" className="font-medium text-slate-900 underline">Register</Link>
      </p>
      <p className="mt-3 rounded bg-slate-100 p-2 text-xs text-slate-500">
        Demo: admin@example.com / password (admin) · user@example.com / password
      </p>
    </div>
  );
}

function flatten(errors) {
  return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
}
