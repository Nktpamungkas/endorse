/**
 * Ikon SVG ringan (stroke, currentColor). Tidak ada dependency eksternal.
 * Pakai: <Plus size={16} className="text-slate-400" />
 */

function Svg({ size = 16, className = '', children }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      className={className}
    >
      {children}
    </svg>
  );
}

export function Plus(props) {
  return (<Svg {...props}><path d="M12 5v14M5 12h14" /></Svg>);
}

export function Dots(props) {
  return (
    <svg width={props.size || 16} height={props.size || 16} viewBox="0 0 24 24" fill="currentColor" className={props.className || ''}>
      <circle cx="5" cy="12" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="19" cy="12" r="1.8" />
    </svg>
  );
}

export function Grip(props) {
  return (
    <svg width={props.size || 16} height={props.size || 16} viewBox="0 0 24 24" fill="currentColor" className={props.className || ''}>
      <circle cx="9" cy="6" r="1.6" /><circle cx="15" cy="6" r="1.6" />
      <circle cx="9" cy="12" r="1.6" /><circle cx="15" cy="12" r="1.6" />
      <circle cx="9" cy="18" r="1.6" /><circle cx="15" cy="18" r="1.6" />
    </svg>
  );
}

export function Close(props) {
  return (<Svg {...props}><path d="M6 6l12 12M18 6 6 18" /></Svg>);
}

export function Pencil(props) {
  return (<Svg {...props}><path d="M12 20h9" /><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" /></Svg>);
}

export function Trash(props) {
  return (<Svg {...props}><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14" /></Svg>);
}

export function ExternalLink(props) {
  return (<Svg {...props}><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" /><polyline points="15 3 21 3 21 9" /><line x1="10" y1="14" x2="21" y2="3" /></Svg>);
}
