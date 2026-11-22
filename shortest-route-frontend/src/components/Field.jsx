/** Small labelled input used by the auth forms. */
export default function Field({ label, error, ...props }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
      <input
        {...props}
        className={`w-full rounded-lg border px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 ${
          error ? 'border-red-400' : 'border-slate-300'
        }`}
      />
      {error && <span className="mt-1 block text-sm text-red-600">{error}</span>}
    </label>
  );
}
