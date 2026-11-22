import { useCallback, useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import api, { errorMessage } from '../api';
import { useAuth } from '../AuthContext.jsx';
import GraphView from '../components/GraphView.jsx';
import HistoryTable from '../components/HistoryTable.jsx';
import AddNodeForm from '../components/AddNodeForm.jsx';

export default function Calculator() {
  const { user } = useAuth();

  const [nodes, setNodes] = useState([]);
  const [history, setHistory] = useState([]);
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [result, setResult] = useState(null); // { nodeNames, distance }
  const [error, setError] = useState('');
  const [loadingNodes, setLoadingNodes] = useState(true);
  const [loadingHistory, setLoadingHistory] = useState(true);
  const [calculating, setCalculating] = useState(false);

  const loadNodes = useCallback(async () => {
    try {
      const { data } = await api.get('/nodes');
      setNodes(data);
    } catch (err) {
      toast.error(errorMessage(err));
    } finally {
      setLoadingNodes(false);
    }
  }, []);

  const loadHistory = useCallback(async () => {
    try {
      const { data } = await api.get('/shortest-path/history');
      setHistory(data);
    } catch (err) {
      toast.error(errorMessage(err));
    } finally {
      setLoadingHistory(false);
    }
  }, []);

  useEffect(() => {
    loadNodes();
    loadHistory();
  }, [loadNodes, loadHistory]);

  const calculate = async (e) => {
    e.preventDefault();
    setError('');
    setResult(null);

    // client-side validation
    if (!from || !to) return setError('Please choose both a FROM and a TO node.');
    if (from === to) return setError('FROM and TO must be different nodes.');

    setCalculating(true);
    try {
      const { data } = await api.post('/shortest-path', { fromNode: from, toNode: to });
      setResult(data);
      if (data.nodeNames.length === 0) {
        setError(`No path exists from ${from} to ${to}.`);
        toast.error('No path exists');
      } else {
        toast.success('Route found');
      }
      loadHistory();
    } catch (err) {
      const msg = errorMessage(err);
      setError(msg);
      toast.error(msg);
    } finally {
      setCalculating(false);
    }
  };

  const found = result && result.nodeNames.length > 0;

  return (
    <div className="space-y-6">
      {/* Calculator */}
      <section className="rounded-2xl bg-white p-5 shadow">
        <h2 className="mb-4 text-xl font-semibold">Route calculator</h2>

        <form onSubmit={calculate} className="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
          <Select label="FROM" value={from} onChange={setFrom} nodes={nodes} disabled={loadingNodes} />
          <Select label="TO" value={to} onChange={setTo} nodes={nodes} disabled={loadingNodes} />
          <button
            disabled={calculating || loadingNodes}
            className="self-end rounded-lg bg-yellow-400 px-6 py-2.5 font-semibold text-slate-900 hover:bg-yellow-300 disabled:opacity-60"
          >
            {calculating ? 'Calculating…' : 'Calculate'}
          </button>
        </form>

        {error && !found && (
          <p role="alert" className="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-amber-800">
            {error}
          </p>
        )}

        {found && (
          <div className="mt-4 rounded-xl border-2 border-yellow-400 bg-yellow-50 p-4">
            <p className="text-sm uppercase tracking-wide text-slate-500">Shortest path</p>
            <p className="mt-1 flex flex-wrap items-center gap-2 text-2xl font-bold">
              {result.nodeNames.map((n, i) => (
                <span key={`${n}-${i}`} className="flex items-center gap-2">
                  <span className="grid h-10 w-10 place-items-center rounded-full bg-yellow-400">{n}</span>
                  {i < result.nodeNames.length - 1 && <span className="text-slate-400">→</span>}
                </span>
              ))}
            </p>
            <p className="mt-3 text-slate-700">{result.nodeNames.join(', ')}</p>
            <p className="text-lg font-semibold">Total Distance: {result.distance}</p>
          </div>
        )}
      </section>

      {/* Graph visualisation (bonus) */}
      <section className="rounded-2xl bg-white p-5 shadow">
        <h2 className="mb-3 text-xl font-semibold">Graph</h2>
        {loadingNodes ? (
          <p className="text-sm text-slate-500">Loading graph…</p>
        ) : (
          <GraphView nodes={nodes} path={found ? result.nodeNames : undefined} />
        )}
      </section>

      {/* Admin: add node */}
      {user?.is_admin && (
        <section className="rounded-2xl bg-white p-5 shadow">
          <h2 className="mb-3 text-xl font-semibold">Add node</h2>
          <AddNodeForm nodes={nodes} onCreated={loadNodes} />
        </section>
      )}

      {/* History */}
      <section className="rounded-2xl bg-white p-5 shadow">
        <h2 className="mb-3 text-xl font-semibold">Your path history</h2>
        <HistoryTable rows={history} loading={loadingHistory} />
      </section>
    </div>
  );
}

function Select({ label, value, onChange, nodes, disabled }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
        className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:ring-2 focus:ring-yellow-400"
      >
        <option value="">{disabled ? 'Loading…' : 'Select node'}</option>
        {nodes.map((n) => (
          <option key={n.name} value={n.name}>{n.name}</option>
        ))}
      </select>
    </label>
  );
}
