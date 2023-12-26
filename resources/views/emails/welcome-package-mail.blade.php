@component('mail::message')
{{-- Introduction  --}}

<label for="">Hi <b>{{ ucfirst($user->fname) }} {{ ucfirst($user->lname) }}</b>, </label>
<p>
    Thank you for joining the <b>{{ config('app.name') }}</b> Team! We've created this app as a space for entrepreneurs like “YOU”. Your Digital Portfolio will be the place for you to showcase your skills and book clients.
</p>
<!-- <p>
    Thank you for registering to our application.
    We build <b>{{ config('app.name') }}</b> to help providers to share their services to global level.
    We have created this video for your references to explore the app and setup your profile.
</p> -->

<p>
We've created this video for you to explore the app and set up your Digital Portfolio. 
    <!-- Here are the next steps for how to use our app and setup the profile. -->
</p>

<br />
<br />
<embed width="640" height="385" base="https://www.youtube.com/v/" wmode="opaque" id="swfContainer0" type="application/x-shockwave-flash" src="https://www.youtube.com/v/Bk_6r-b3kqU?border=0&autoplay=0&client=ytapi-google-gmail&version=3&start=0">
<br />
<br />

<p>Welcome to the <b>{{ config('app.name') }}</b> Team!</p>
<br />
All the best,<br>
{{ config('app.name') }}
@endcomponent