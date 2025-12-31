import React, { useEffect, useState } from 'react';
import { supabase } from '../supabaseClient';

const Dashboard = () => {
  const [breweries, setBreweries] = useState([]);
  const [recipes, setRecipes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);

  // 'list' | 'add-recipe' | 'edit-recipe' | 'login'
  const [view, setView] = useState('list');

  // Forms state
  const [formData, setFormData] = useState({});

  // Login state
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  useEffect(() => {
    const fetchSession = async () => {
      const { data: { session } } = await supabase.auth.getSession();
      setUser(session?.user ?? null);
      if (session?.user) {
        fetchData(session.user.id);
      } else {
        setView('login');
        setLoading(false);
      }
    };
    fetchSession();

    const { data: { subscription } } = supabase.auth.onAuthStateChange((_event, session) => {
      setUser(session?.user ?? null);
      if (session?.user) {
        fetchData(session.user.id);
        setView('list');
      } else {
        setBreweries([]);
        setRecipes([]);
        setView('login');
      }
    });

    return () => subscription.unsubscribe();
  }, []);

  const fetchData = async (userId) => {
    setLoading(true);
    // Fetch Breweries (assuming RLS or user_id check)
    const { data: brewData } = await supabase
      .from('breweries')
      .select('*');
      //.eq('user_id', userId); // Uncomment if explicit filter needed

    if (brewData) setBreweries(brewData);

    // Fetch Recipes
    const { data: recipeData } = await supabase
      .from('recipes')
      .select('*');
      //.eq('user_id', userId); // Uncomment if explicit filter needed

    if (recipeData) setRecipes(recipeData);

    setLoading(false);
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    const { error } = await supabase.auth.signInWithPassword({
      email,
      password,
    });
    if (error) alert(error.message);
    setLoading(false);
  };

  const handleSaveRecipe = async (e) => {
    e.preventDefault();
    if (!user) return;

    const payload = { ...formData, user_id: user.id };

    let error;
    if (view === 'add-recipe') {
      const { data, error: err } = await supabase.from('recipes').insert([payload]).select();
      error = err;
      if (data) setRecipes([...recipes, ...data]);
    } else if (view === 'edit-recipe') {
      const { error: err } = await supabase.from('recipes').update(payload).eq('id', formData.id);
      error = err;
      if (!err) {
        setRecipes(recipes.map(r => r.id === formData.id ? { ...r, ...payload } : r));
      }
    }

    if (!error) {
      setView('list');
      setFormData({});
    } else {
      alert('Error: ' + error.message);
    }
  };

  const openEdit = (recipe) => {
    setFormData(recipe);
    setView('edit-recipe');
  };

  if (loading && !user) return <div className="p-4 text-center">Chargement...</div>;

  if (view === 'login') return (
    <div className="max-w-xs mx-auto mt-10 p-6 bg-white rounded shadow">
      <h2 className="text-xl font-bold mb-4 text-center">Connexion</h2>
      <form onSubmit={handleLogin}>
        <div className="mb-4">
          <label className="block text-sm font-bold mb-1">Email</label>
          <input
            type="email"
            value={email}
            onChange={e => setEmail(e.target.value)}
            className="w-full border p-2 rounded"
            required
          />
        </div>
        <div className="mb-6">
          <label className="block text-sm font-bold mb-1">Mot de passe</label>
          <input
            type="password"
            value={password}
            onChange={e => setPassword(e.target.value)}
            className="w-full border p-2 rounded"
            required
          />
        </div>
        <button type="submit" className="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
          Se connecter
        </button>
      </form>
    </div>
  );

  return (
    <div className="beer-dashboard p-4 bg-gray-50 min-h-[300px] rounded-lg shadow">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold text-beer-dark">Mon Beer Hub</h2>
        <button onClick={() => supabase.auth.signOut()} className="text-sm text-red-500 hover:underline">
          Déconnexion
        </button>
      </div>

      {view === 'list' && (
        <>
          <div className="mb-6 flex gap-2">
            <button
              onClick={() => { setFormData({}); setView('add-recipe'); }}
              className="bg-beer-gold text-white px-4 py-2 rounded font-bold hover:bg-orange-600"
            >
              Ajouter une Recette
            </button>
          </div>

          <div className="space-y-8">
            {/* Breweries Section */}
            <div>
              <h3 className="text-xl font-bold mb-4 border-b pb-2">Mes Brasseries</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {breweries.map(brew => (
                  <div key={brew.id} className="bg-white p-4 rounded shadow border border-gray-100">
                    <h4 className="font-bold text-lg">{brew.name}</h4>
                    <p className="text-sm text-gray-500">{brew.location || 'Lieu inconnu'}</p>
                  </div>
                ))}
                {breweries.length === 0 && <p className="text-gray-500 italic">Aucune brasserie.</p>}
              </div>
            </div>

            {/* Recipes Section */}
            <div>
              <h3 className="text-xl font-bold mb-4 border-b pb-2">Mes Recettes</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {recipes.map(recipe => (
                  <div key={recipe.id} className="bg-white p-4 rounded shadow border border-gray-100 relative group">
                     <button
                        onClick={() => openEdit(recipe)}
                        className="absolute top-2 right-2 text-gray-400 hover:text-blue-500 opacity-0 group-hover:opacity-100 transition"
                     >
                       Modifier
                     </button>
                    <h4 className="font-bold text-lg text-beer-dark">{recipe.name}</h4>
                    <p className="text-sm text-gray-600">{recipe.style}</p>
                    <div className="mt-2 text-beer-gold font-semibold">{recipe.abv}% ABV</div>
                  </div>
                ))}
                {recipes.length === 0 && <p className="text-gray-500 italic">Aucune recette.</p>}
              </div>
            </div>
          </div>
        </>
      )}

      {(view === 'add-recipe' || view === 'edit-recipe') && (
        <form onSubmit={handleSaveRecipe} className="bg-white p-6 rounded shadow max-w-md mx-auto">
          <h3 className="text-xl font-bold mb-4">{view === 'add-recipe' ? 'Nouvelle Recette' : 'Modifier la Recette'}</h3>

          <div className="mb-4">
            <label className="block text-gray-700 text-sm font-bold mb-2">Nom</label>
            <input
              type="text"
              value={formData.name || ''}
              onChange={e => setFormData({...formData, name: e.target.value})}
              className="w-full border p-2 rounded"
              required
            />
          </div>

          <div className="mb-4">
            <label className="block text-gray-700 text-sm font-bold mb-2">Style</label>
            <input
              type="text"
              value={formData.style || ''}
              onChange={e => setFormData({...formData, style: e.target.value})}
              className="w-full border p-2 rounded"
            />
          </div>

          <div className="mb-4">
            <label className="block text-gray-700 text-sm font-bold mb-2">ABV (%)</label>
            <input
              type="number"
              step="0.1"
              value={formData.abv || ''}
              onChange={e => setFormData({...formData, abv: e.target.value})}
              className="w-full border p-2 rounded"
            />
          </div>

          <div className="flex gap-2">
            <button type="button" onClick={() => setView('list')} className="w-1/2 bg-gray-200 text-gray-700 py-2 px-4 rounded hover:bg-gray-300 transition">
              Annuler
            </button>
            <button type="submit" className="w-1/2 bg-beer-gold text-white font-bold py-2 px-4 rounded hover:bg-orange-600 transition">
              Enregistrer
            </button>
          </div>
        </form>
      )}
    </div>
  );
};

export default Dashboard;
