
<!DOCTYPE html>
<html lang="en">

<body>
<p>Dear {{ $user->fname }}</p>
<p>Your account has been created, please activate your account by clicking this link</p>

<p><a href="{{ route('verification.verify',$user->email_verification_token) }}">
	{{ route('verification.verify',$user->email_verification_token) }}
</a></p>

</br>


<p>Thanks</p>

</body>

</html> 
