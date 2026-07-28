class CirclePostsController < AuthenticatedController
  def create
    post = current_user.circle_posts.new(circle_post_params)

    if post.save
      redirect_to dashboard_path, notice: "Post publicado no ciclo."
    else
      redirect_back fallback_location: dashboard_path, alert: post.errors.full_messages.first
    end
  end

  def reply
    post = CirclePost.find(params[:circle_post_id] || params[:id])
    head :forbidden and return unless current_user.is_circle_member_with?(post.user)

    reply = post.replies.new(body: params.dig(:reply, :body).to_s.strip, user: current_user)
    if reply.save
      redirect_to dashboard_path, notice: "Resposta enviada."
    else
      redirect_back fallback_location: dashboard_path, alert: reply.errors.full_messages.first
    end
  end

  private

  def circle_post_params
    params.require(:circle_post).permit(:title, :body).to_h.transform_values { |value| value.to_s.strip }
  end
end
