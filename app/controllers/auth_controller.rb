class AuthController < ApplicationController
  def login
    return redirect_to dashboard_path if authenticated?

    render :login
  end

  def register
    return redirect_to dashboard_path if authenticated?

    render :register
  end

  def login_attempt
    user_params = params.require(:auth).permit(:email, :password, :remember)
    user = User.find_by(email: user_params[:email].to_s.strip.downcase)

    if user&.authenticate(user_params[:password])
      reset_session
      session[:user_id] = user.id
      user.update!(last_login_at: Time.current)
      redirect_to session.delete(:return_to).presence || dashboard_path
    else
      flash.now[:alert] = "E-mail ou senha inválidos."
      @email = user_params[:email]
      render :login, status: :unprocessable_entity
    end
  end

  def register_store
    permitted = params.require(:auth).permit(:username, :display_name, :email, :password, :password_confirmation, :bio)
    user = User.new(
      username: permitted[:username].to_s.strip,
      display_name: permitted[:display_name].presence&.strip,
      email: permitted[:email].to_s.strip.downcase,
      password: permitted[:password],
      password_confirmation: permitted[:password_confirmation],
      bio: permitted[:bio].presence&.strip,
      last_login_at: Time.current
    )

    if user.save
      reset_session
      session[:user_id] = user.id
      redirect_to dashboard_path
    else
      flash.now[:alert] = user.errors.full_messages.first || "Revise os campos abaixo."
      @user = user
      render :register, status: :unprocessable_entity
    end
  end

  def logout
    reset_session
    redirect_to root_path
  end
end
