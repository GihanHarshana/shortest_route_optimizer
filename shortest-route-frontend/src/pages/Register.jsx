import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../AuthContext.jsx';
import { errorMessage } from '../api';
import Field from '../components/Field.jsx';

export default function Register() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();

    const c = {};
    if (!form.name.trim()) c.name = 'Name is required';
    if (!form.email.trim()) c.email = 'Email is required';
    if (form.password.length < 8) c.password = 'Password must be at least 8 characters';
    if (form.password !== form.password_confirmation) c.password_confirmation = 'Passwords do not match';
    setErrors(c);
    if (Object.keys(c).length) return;

    setLoading(true);
    try {
      await register(form);
      toast.success('Account created');
      navigate('/');
    } catch (err) {
      const apiErrors = err.response?.data?.errors;
      setErrors(apiErrors ? Object.fromEntries(Object.entries(apiErrors).map(([k, v]) => [k, v[0]])) : {});
      toast.error(errorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="mx-auto max-w-md rounded-2xl bg-white p-6 shadow">
      <h1 className="mb-4 text-2xl font-semibold">Create an account</h1>
      <form onSubmit={submit} className="space-y-4" noValidate>
        <Field label="Name" value={form.name} onChange={set('name')} error={errors.name} autoComplete="name" />
        <Field label="Email" type="email" value={form.email} onChange={set('email')} error={errors.email} autoComplete="email" />
        <Field label="Password" type="password" value={form.password} onChange={set('password')} error={errors.password} autoComplete="new-password" />
        <Field label="Confirm password" type="password" value={form.password_confirmation} onChange={set('password_confirmation')} error={errors.password_confirmation} autoComplete="new-password" />
        <button disabled={loading} className="w-full rounded-lg bg-slate-900 py-2.5 font-medium text-white hover:bg-slate-800 disabled:opacity-60">
          {loading ? 'Creating…' : 'Register'}
        </button>
      </form>
      <p className="mt-4 text-sm text-slate-600">
        Already registered? <Link to="/login" className="font-medium text-slate-900 underline">Log in</Link>
      </p>
    </div>
  );
}
