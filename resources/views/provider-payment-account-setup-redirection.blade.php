
<html><head>
   <title>LinkMe, Inc</title>
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="icon" type="image/png" href="https://dhybix25iw1zg.cloudfront.net/static/favicon.png">
   <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700,800,900&amp;display=swap" rel="stylesheet">
   <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
   <meta http-equiv="Pragma" content="no-cache">
   <meta http-equiv="Expires" content="0">

<script type="text/javascript">

function notifyNative(){
   var pairs = location.search.slice(1).split('&');
   var result = {};
   pairs.forEach(function(pair) {
       pair = pair.split('=');
       result[pair[0]] = decodeURIComponent(pair[1] || '');
   });

   data = JSON.stringify(result);
   window.ReactNativeWebView.postMessage(data);
}

</script>
<style type="text/css">
.logohead {
   text-align: center;
   padding: 20vh 0 10vh 0;
   border-bottom: 1px solid #fd6e56;
}
.logohead img {
   display: inline-block;
   vertical-align: middle;
}
.providerpayment h1{
   font-size: 16px;
   padding: 6vh 2vh 15vh 2vh;
}
.providerpayment {
   text-align: center;
}
.providerpayment input[type="button"] {
   background: #fd6e56;
   border: 0;
   color: #fff;
   font-size: 17px;
   padding: 11px 25px 10px 25px;
   font-weight: bold;
   border-radius: 18px;
   outline: none;
}
.providerpayment input[type="button"]:hover{
    background: #000;
}
</style>
</head>
<body class="providerpayment">
       <div class="logohead">            
           <img src="{{config('filesystems.disks.s3.url').'/static/'}}linkme-logo-v1.png" alt="LinkMe" width="50px">
           <img src="{{config('filesystems.disks.s3.url').'/static/'}}connected.png" alt="LinkMe" width="35px">
           <img src="{{config('filesystems.disks.s3.url').'/static/'}}Stripe_logo.png" alt="LinkMe" width="100px">
       </div>
       <h1><strong>Your stripe account is setup successfully. Kindly continue with the setup.</strong></h1>
       <input type="button" value="Continue" onclick="notifyNative();">
</body></html>

