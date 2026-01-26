from flask import Flask, render_template, request, redirect, session, url_for, g, flash
import sqlite3
import os

app = Flask(__name__)
app.secret_key = 'super_secret_key_corp_forum'
app.config['DATABASE'] = os.path.join(os.path.dirname(__file__), 'forum.db')

def get_db():
    db = getattr(g, '_database', None)
    if db is None:
        db = g._database = sqlite3.connect(app.config['DATABASE'])
        db.row_factory = sqlite3.Row
    return db

@app.teardown_appcontext
def close_connection(exception):
    db = getattr(g, '_database', None)
    if db is not None:
        db.close()

# Context Processor for User
@app.context_processor
def inject_user():
    user = None
    if 'user_id' in session:
        cur = get_db().cursor()
        cur.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],))
        user = cur.fetchone()
    return dict(current_user=user)

# Routes

@app.route('/')
def index():
    cur = get_db().cursor()
    cur.execute("SELECT * FROM categories")
    categories = cur.fetchall()
    
    # Also fetch recent threads
    cur.execute("""
        SELECT t.*, u.username, c.name as category_name, c.slug as category_slug
        FROM threads t 
        JOIN users u ON t.user_id = u.id 
        JOIN categories c ON t.category_id = c.id
        ORDER BY t.created_at DESC LIMIT 5
    """)
    recent_threads = cur.fetchall()
    
    return render_template('index.html', categories=categories, recent_threads=recent_threads)

@app.route('/category/<slug>')
def category_view(slug):
    db = get_db()
    cur = db.cursor()
    cur.execute("SELECT * FROM categories WHERE slug = ?", (slug,))
    category = cur.fetchone()
    
    if not category:
        return "Category not found", 404
        
    # Search implementation with SQLi
    search_query = request.args.get('search')
    
    sql = f"""
        SELECT t.*, u.username 
        FROM threads t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.category_id = {category['id']}
    """
    
    if search_query:
        # VULN: SQL Injection
        # Directly injecting search term relative to existing query part
        sql += f" AND (t.title LIKE '%{search_query}%')"
        
    sql += " ORDER BY t.is_pinned DESC, t.created_at DESC"
    
    try:
        cur.execute(sql)
        threads = cur.fetchall()
    except Exception as e:
        # VULN: Error info leak
        return f"Database Error: {e}"
        
    return render_template('category.html', category=category, threads=threads)

@app.route('/thread/<int:thread_id>')
def thread_view(thread_id):
    db = get_db()
    cur = db.cursor()
    
    # Increment views
    cur.execute("UPDATE threads SET views = views + 1 WHERE id = ?", (thread_id,))
    db.commit()
    
    cur.execute("""
        SELECT t.*, u.username, c.name as category_name, c.slug as category_slug
        FROM threads t 
        JOIN users u ON t.user_id = u.id
        JOIN categories c ON t.category_id = c.id
        WHERE t.id = ?
    """, (thread_id,))
    thread = cur.fetchone()
    
    if not thread:
        return "Thread not found", 404
        
    cur.execute("""
        SELECT p.*, u.username, u.avatar, u.role, u.reputation
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.thread_id = ?
        ORDER BY p.created_at ASC
    """, (thread_id,))
    posts = cur.fetchall()
    
    return render_template('thread.html', thread=thread, posts=posts)

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        # VULN: SQL Injection in Login
        sql = f"SELECT * FROM users WHERE username = '{username}' AND password = '{password}'"
        cur = get_db().cursor()
        try:
            cur.execute(sql)
            user = cur.fetchone()
            if user:
                session['user_id'] = user['id']
                flash('Welcome back!')
                return redirect(url_for('index'))
            else:
                flash('Invalid credentials')
        except Exception as e:
            return f"DB Error: {e}"
            
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        db = get_db()
        cur = db.cursor()
        try:
            cur.execute("INSERT INTO users (username, password, avatar) VALUES (?, ?, ?)", 
                       (username, password, f"https://ui-avatars.com/api/?name={username}"))
            db.commit()
            flash('Registration successful')
            return redirect(url_for('login'))
        except:
            flash('Username taken')
            
    return render_template('register.html')

@app.route('/logout')
def logout():
    session.pop('user_id', None)
    return redirect(url_for('index'))

@app.route('/profile', methods=['GET', 'POST'])
def profile():
    if 'user_id' not in session:
        return redirect(url_for('login'))
        
    if request.method == 'POST':
        # VULN: CSRF (No token check)
        email = request.form['email']
        db = get_db()
        db.execute("UPDATE users SET email = ? WHERE id = ?", (email, session['user_id']))
        db.commit()
        flash('Profile updated')
    
    return render_template('profile.html')

@app.route('/new_thread', methods=['GET', 'POST'])
def new_thread():
    if 'user_id' not in session:
        return redirect(url_for('login'))
        
    if request.method == 'POST':
        title = request.form['title']
        content = request.form['content']
        category_id = request.form['category_id']
        
        db = get_db()
        cur = db.cursor()
        
        # VULN: XSS (Stored) - Title is not sanitized when output in templates (using |safe or autoescape false)
        cur.execute("INSERT INTO threads (category_id, user_id, title) VALUES (?, ?, ?)",
                   (category_id, session['user_id'], title))
        thread_id = cur.lastrowid
        
        cur.execute("INSERT INTO posts (thread_id, user_id, content) VALUES (?, ?, ?)",
                   (thread_id, session['user_id'], content))
        db.commit()
        
        return redirect(url_for('thread_view', thread_id=thread_id))

    cur = get_db().cursor()
    cur.execute("SELECT * FROM categories")
    categories = cur.fetchall()
    return render_template('new_thread.html', categories=categories)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)