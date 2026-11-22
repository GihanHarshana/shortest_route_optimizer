import { useState } from 'react';
import toast from 'react-hot-toast';
import api, { errorMessage } from '../api';

/** Admin-only form: POST /api/nodes */
export default function AddNodeForm({ nodes, onCreated }) {
  const [name, setName] = useState('');
  const [connections, setConnections] = useState([]); // [{ toNode, distance }]
  const [saving, setSaving] = useState(false);

  const update = (i, patch) => setConnections(connections.map((c, idx) => (idx === i ? { ...c, ...patch } : c)));

  const submit = async (e) => {
    e.preventDefault();
    if (!name.trim()) return toast.error('Node name is required');
    if (connections.some((c) => !c.toNode || !(Number(c.distance) > 0))) {
      return toast.error('Each connection needs a target and a positive distance');
    }

    setSaving(true);
    try {
      await api.post('/nodes', {
        name: name.trim(),
        connections: connections.map((c) => ({ toNode: c.toNode, distance: Number(c.distance) })),
      });
      toast.success(`Node ${name.trim()} created`);
      setName('');
      setConnections([]);
      onCreated();
    } catch (err) {
      toast.error(errorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  return (
    <form onSubmit={submit} className="space-y-3">
      <input
        value={name}
        onChange={(e) => setName(e.target.value)}
        placeholder="New node name, e.g. G"
        className="w-full rounded-lg border border-slate-300 px-3 py-2"
      />

      {connections.map((c, i) => (
        <div key={i} className="flex gap-2">
          <select value={c.toNode} onChange={(e) => update(i, { toNode: e.target.value })} className="flex-1 rounded-lg border border-slate-300 px-2 py-2">
            <option value="">→ to node…</option>
            {nodes.map((n) => <option key={n.name} value={n.name}>{n.name}</option>)}
          </select>
          <input
            type="number" min="1" value={c.distance} onChange={(e) => update(i, { distance: e.target.value })}
            placeholder="dist" className="w-24 rounded-lg border border-slate-300 px-2 py-2"
          />
          <button type="button" onClick={() => setConnections(connections.filter((_, idx) => idx !== i))} className="px-2 text-red-600" aria-label="Remove connection">✕</button>
        </div>
      ))}

      <div className="flex flex-wrap gap-2">
        <button type="button" onClick={() => setConnections([...connections, { toNode: '', distance: '' }])} className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
          + Add connection
        </button>
        <button disabled={saving} className="rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60">
          {saving ? 'Saving…' : 'Create node'}
        </button>
      </div>
      <p className="text-xs text-slate-500">Connections can only point to nodes that already exist.</p>
    </form>
  );
}
