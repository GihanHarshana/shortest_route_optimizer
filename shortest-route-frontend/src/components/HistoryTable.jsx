export default function HistoryTable({ rows, loading }) {
  if (loading) return <p className="text-sm text-slate-500">Loading history…</p>;
  if (!rows.length) return <p className="text-sm text-slate-500">No calculations yet.</p>;

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[480px] text-left text-sm">
        <thead className="border-b text-slate-500">
          <tr>
            <th className="py-2 pr-3 font-medium">From</th>
            <th className="py-2 pr-3 font-medium">To</th>
            <th className="py-2 pr-3 font-medium">Path</th>
            <th className="py-2 pr-3 font-medium">Distance</th>
            <th className="py-2 font-medium">When</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((r) => (
            <tr key={r.id} className="border-b last:border-0">
              <td className="py-2 pr-3 font-medium">{r.fromNode}</td>
              <td className="py-2 pr-3 font-medium">{r.toNode}</td>
              <td className="py-2 pr-3">
                {r.nodeNames.length ? r.nodeNames.join(', ') : <span className="text-amber-600">No path</span>}
              </td>
              <td className="py-2 pr-3">{r.nodeNames.length ? r.distance : '—'}</td>
              <td className="py-2 text-slate-500">{new Date(r.createdAt).toLocaleString()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
