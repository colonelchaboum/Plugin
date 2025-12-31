import React, { useEffect, useState } from 'react';
import { supabase } from '../supabaseClient';

const ProjectManager = () => {
  const [projects, setProjects] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProjectsAndTasks();
  }, []);

  const fetchProjectsAndTasks = async () => {
    setLoading(true);
    // Use Supabase relationship fetching to avoid N+1
    // Assuming foreign key relation exists: project_tasks.project_id -> projects.id
    const { data: projs, error } = await supabase
      .from('projects')
      .select('*, project_tasks(*)')
      .order('created_at', { ascending: false });

    if (projs) {
      // Sort tasks by id within projects if needed
      const projectsWithSortedTasks = projs.map(p => ({
        ...p,
        project_tasks: (p.project_tasks || []).sort((a, b) => a.id - b.id)
      }));
      setProjects(projectsWithSortedTasks);
    } else if (error) {
      console.error('Error fetching projects:', error);
    }
    setLoading(false);
  };

  const toggleTask = async (taskId, currentStatus, projectId) => {
    const { error } = await supabase
      .from('project_tasks')
      .update({ is_completed: !currentStatus })
      .eq('id', taskId);

    if (!error) {
      // Optimistic update
      setProjects(prevProjects => prevProjects.map(p => {
        if (p.id !== projectId) return p;
        return {
          ...p,
          project_tasks: p.project_tasks.map(t =>
            t.id === taskId ? { ...t, is_completed: !currentStatus } : t
          )
        };
      }));
    } else {
        console.error('Error updating task:', error);
    }
  };

  const calculateProgress = (tasks) => {
    if (!tasks || tasks.length === 0) return 0;
    const completed = tasks.filter(t => t.is_completed).length;
    return Math.round((completed / tasks.length) * 100);
  };

  if (loading && projects.length === 0) return <div className="p-4">Chargement des projets...</div>;

  return (
    <div className="project-manager p-4 bg-gray-50 rounded-lg">
      <h2 className="text-2xl font-bold mb-6 text-beer-dark">Gestion de Projets Brasserie</h2>

      <div className="space-y-6">
        {projects.map(project => {
          const tasks = project.project_tasks || [];
          const progress = calculateProgress(tasks);

          return (
            <div key={project.id} className="bg-white rounded shadow p-4 border-l-4 border-beer-gold">
              <div className="flex justify-between items-center mb-2">
                <h3 className="font-bold text-xl">{project.title || 'Projet sans titre'}</h3>
                <span className="text-sm font-mono bg-gray-100 px-2 py-1 rounded">{progress}%</span>
              </div>

              {/* Progress Bar */}
              <div className="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                <div
                  className="bg-beer-gold h-2.5 rounded-full transition-all duration-500"
                  style={{ width: `${progress}%` }}
                ></div>
              </div>

              {/* Tasks */}
              <div className="space-y-2">
                {tasks.map(task => (
                  <div
                    key={task.id}
                    className="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
                    onClick={() => toggleTask(task.id, task.is_completed, project.id)}
                  >
                    <input
                      type="checkbox"
                      checked={task.is_completed}
                      onChange={() => {}} // Handled by div click
                      className="w-4 h-4 text-beer-gold rounded focus:ring-beer-gold cursor-pointer"
                    />
                    <span className={`ml-3 text-sm ${task.is_completed ? 'line-through text-gray-400' : 'text-gray-700'}`}>
                      {task.title || task.description}
                    </span>
                  </div>
                ))}
                {tasks.length === 0 && (
                  <p className="text-xs text-gray-400 italic">Aucune tâche pour ce projet.</p>
                )}
              </div>
            </div>
          );
        })}
        {projects.length === 0 && !loading && (
          <p className="text-gray-500">Aucun projet trouvé.</p>
        )}
      </div>
    </div>
  );
};

export default ProjectManager;
