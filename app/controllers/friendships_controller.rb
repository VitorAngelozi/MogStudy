class FriendshipsController < AuthenticatedController
  def create
    target = User.find(params[:user_id])
    return redirect_back fallback_location: dashboard_path, alert: "Você não pode adicionar a si mesmo." if target.id == current_user.id

    existing = Friendship.where(requester_id: current_user.id, addressee_id: target.id)
                         .or(Friendship.where(requester_id: target.id, addressee_id: current_user.id))
                         .exists?
    return redirect_back fallback_location: dashboard_path, alert: "Já existe um pedido ou amizade com esse usuário." if existing

    Friendship.create!(requester_id: current_user.id, addressee_id: target.id, status: Friendship::STATUS_PENDING)
    redirect_to dashboard_path, notice: "Pedido de amizade enviado."
  end

  def accept
    friendship = Friendship.find(params[:id])
    head :forbidden and return unless friendship.addressee_id == current_user.id

    friendship.update!(status: Friendship::STATUS_ACCEPTED)
    redirect_to dashboard_path, notice: "Pedido de amizade aceito."
  end

  def destroy
    friendship = Friendship.find(params[:id])
    head :forbidden and return unless friendship.involves?(current_user.id)

    friendship.destroy!
    redirect_to dashboard_path, notice: "Amizade atualizada."
  end
end
