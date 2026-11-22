import { Link, useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useAuth } from '../AuthContext.jsx';

export default function Navbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const onLogout = async () => {
    await logout();
    toast.success('Logged out');
    navigate('/login');
  };

  return (
    <header className="bg-slate-900 text-white">
      <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-3">
        <Link to="/" className="flex items-center gap-2 text-lg font-semibold">
          <span className="grid h-7 w-7 place-items-center rounded-full bg-yellow-400 text-sm font-bold text-slate-900">
            A
          </span>
          Shortest Route Optimizer
        </Link>

        <nav className="flex items-center gap-3 text-sm">
          {user ? (
            <>
              <span className="hidden text-slate-300 sm:inline">
                {user.name}
                {user.is_admin && (
                  <span className="ml-2 rounded bg-yellow-400 px-1.5 py-0.5 text-xs font-semibold text-slate-900">
                    admin
                  </span>
                )}
              </span>
              <button onClick={onLogout} className="rounded bg-slate-700 px-3 py-1.5 hover:bg-slate-600">
                Log out
              </button>
            </>
          ) : (
            <>
              <Link to="/login" className="hover:underline">Login</Link>
              <Link to="/register" className="rounded bg-yellow-400 px-3 py-1.5 font-medium text-slate-900 hover:bg-yellow-300">
                Register
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
