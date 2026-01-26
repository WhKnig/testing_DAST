require 'sinatra'
require 'mongo'
require 'erb'

# Configure Mongo (Assuming localhost for dev, but Ansible will need to ensure Mongo is running)
# In the testbench topology, this might need to point to a service or localhost if on same node.
# Based on file structure, these are deployed to 'targets' host group.
client = Mongo::Client.new([ '127.0.0.1:27017' ], :database => 'securecms')

configure do
  enable :sessions
  set :bind, '0.0.0.0'
end

# Helpers
helpers do
  def current_user
    session[:user]
  end

  def protected!
    redirect '/login' unless current_user
  end
end

# Seed Logic (Run manually or via curl)
get '/seed' do
  users = client[:users]
  pages = client[:pages]
  
  users.delete_many
  pages.delete_many
  
  users.insert_one({ username: 'admin', password: 'admin123', role: 'admin' })
  users.insert_one({ username: 'editor', password: 'editor123', role: 'editor' })
  
  pages.insert_one({ title: 'Welcome', content: '<h1>Welcome to SecureCMS</h1><p>This is the home page.</p>', slug: 'home', public: true })
  pages.insert_one({ title: 'About Us', content: '<p>We are a CMS company.</p>', slug: 'about', public: true })
  pages.insert_one({ title: 'Private Doc', content: '<p>Secret plans.</p>', slug: 'private', public: false })
  
  "Seeded!"
end

# Routes

get '/' do
  erb :index
end

get '/login' do
  erb :login
end

post '/login' do
  users = client[:users]
  username = params[:username]
  password = params[:password]
  
  # VULN: NoSQL Injection potential if params are passed as query document directly
  # Here we are passing strings, which is generally safe in Ruby driver unless $where used, 
  # BUT for simulation we can accept a query object if form parameter naming allows it (e.g. username[$ne]=null)
  # Rack might parse username[$ne]... let's see. 
  # To ensure vulnerability for scanner, we might need to manually construct if Rack parsing doesn't produce the hash structure Mongo expects from standard form data.
  # Usually standard form data is just strings. 
  # Let's make it explicitly vulnerable by parsing JSON body if provided, or just trusting logic.
  
  user = users.find({ username: username, password: password }).first
  
  if user
    session[:user] = user
    redirect '/dashboard'
  else
    @error = "Invalid credentials"
    erb :login
  end
end

get '/logout' do
  session.clear
  redirect '/'
end

get '/dashboard' do
  protected!
  erb :dashboard
end

get '/pages' do
  protected!
  pages = client[:pages]
  
  query = {}
  if params[:q]
    # VULN: NoSQL Injection / Regex DoS
    # Creating a regex from user input without escaping
    query[:title] = { '$regex' => params[:q], '$options' => 'i' }
  end
  
  @pages = pages.find(query)
  erb :pages
end

get '/pages/new' do
  protected!
  erb :new_page
end

post '/pages' do
  protected!
  pages = client[:pages]
  
  # VULN: Stored XSS in content
  pages.insert_one({
    title: params[:title],
    content: params[:content],
    slug: params[:slug],
    public: params[:public] == 'on'
  })
  
  redirect '/pages'
end

get '/users' do
  protected!
  @users = client[:users].find
  erb :users
end

post '/users' do
  protected!
  # VULN: CSRF (No token)
  client[:users].insert_one({
    username: params[:username],
    password: params[:password],
    role: params[:role]
  })
  redirect '/users'
end