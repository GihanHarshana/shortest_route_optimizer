import { useEffect, useRef } from 'react';
import { Network } from 'vis-network/standalone';

const NO_PATH = [];

// Fixed layout copied from the exam diagram (x → right, y → down).
const FIXED_POSITIONS = {
  A: { x: 0, y: 120 },
  B: { x: 170, y: 0 },
  C: { x: 370, y: 5 },
  D: { x: 545, y: 120 },
  E: { x: 275, y: 225 },
  F: { x: 90, y: 250 },
};

/** Nodes that are not in the diagram (e.g. added by an admin) go in a row underneath. */
function positionFor(name, extraIndex) {
  return FIXED_POSITIONS[name] ?? { x: extraIndex * 110, y: 380 };
}

/**
 * Static directed-graph picture (Vis.js): fixed positions, no dragging, no zoom.
 * Nodes/edges on the shortest path are highlighted (yellow nodes, thick black arrows).
 * Props: nodes = [{ name, connections: [{ toNode, distance }] }], path = ['A','B',...]
 */
export default function GraphView({ nodes, path = NO_PATH }) {
  const container = useRef(null);

  useEffect(() => {
    if (!container.current) return;

    const onPath = new Set(path);
    const pathEdges = new Set();
    for (let i = 0; i < path.length - 1; i++) pathEdges.add(`${path[i]}>${path[i + 1]}`);

    const allEdges = new Set(nodes.flatMap((n) => n.connections.map((c) => `${n.name}>${c.toNode}`)));

    let extra = 0;
    const visNodes = nodes.map((n) => {
      const pos = positionFor(n.name, FIXED_POSITIONS[n.name] ? 0 : extra++);
      const highlighted = onPath.has(n.name);
      return {
        id: n.name,
        label: n.name,
        x: pos.x,
        y: pos.y,
        fixed: true,
        shape: 'circle',
        borderWidth: 2,
        font: { size: 20, face: 'sans-serif', bold: true, color: '#111827' },
        color: highlighted
          ? { background: '#FFE600', border: '#111827' }
          : { background: '#FFFFFF', border: '#111827' },
      };
    });

    const visEdges = nodes.flatMap((n) =>
      n.connections.map((c) => {
        const highlighted = pathEdges.has(`${n.name}>${c.toNode}`);
        const hasReverse = allEdges.has(`${c.toNode}>${n.name}`);
        return {
          from: n.name,
          to: c.toNode,
          label: String(c.distance),
          arrows: { to: { enabled: true, scaleFactor: highlighted ? 1.4 : 0.8 } },
          width: highlighted ? 4 : 1.5,
          color: { color: highlighted ? '#111827' : '#808080' },
          font: { size: 15, strokeWidth: 4, strokeColor: '#ffffff', color: highlighted ? '#111827' : '#555555', align: 'horizontal' },
          // straight lines like the diagram; only curve when both directions exist
          smooth: hasReverse ? { type: 'curvedCW', roundness: 0.2 } : false,
        };
      })
    );

    const network = new Network(
      container.current,
      { nodes: visNodes, edges: visEdges },
      {
        physics: false,
        layout: { improvedLayout: false },
        interaction: {
          dragNodes: false,
          dragView: false,
          zoomView: false,
          selectable: false,
          hover: false,
        },
      }
    );
    network.fit({ animation: false });

    return () => network.destroy();
  }, [nodes, path]);

  return <div ref={container} className="h-80 w-full rounded-xl border border-slate-200 bg-white" />;
}
