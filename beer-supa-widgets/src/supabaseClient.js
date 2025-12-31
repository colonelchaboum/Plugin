import { createClient } from '@supabase/supabase-js';

// supaData is localized from PHP
const supabaseUrl = window.supaData?.supabaseUrl || '';
const supabaseKey = window.supaData?.supabaseKey || '';

export const supabase = createClient(supabaseUrl, supabaseKey);
