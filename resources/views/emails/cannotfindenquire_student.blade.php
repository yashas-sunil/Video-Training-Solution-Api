<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>JKSHAH ONLINE - ENQUIRY</title>
    <style>
        body {
            background-color: #fff;
            max-width: 750px !important; 
            overflow: hidden;
            overflow-y: auto;
            margin: 0 auto;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            border: 3px solid #dd5f2b;
            margin: 20px;
        }

        .container a img {
            width: 200px;
        }

        .container .content {
            padding: 20px 40px;
        }

        .container .content h3,
        .container .content a {
            font-size: 18px;
            line-height: 25px;
            font-weight: 600;
            color: #1a1a1a;
            margin-top: 25px;
            text-decoration: none;
        }

        .container .content a b {
            font-weight: 900;
            color: #000;
        }

        .container .social_media {
            text-align: center;
            border-top: 4px solid #dd5f2b;
            width: 100%;
        }

        .container .social_media h6 {
            font-size: 15px;
            font-weight: 600;
            color: #2e2e2e;
        }

        .container .social_media ul {
            list-style: none;
            display: table;
            margin: auto;
            text-align: center;
            padding: 0;
        }

        .container .social_media ul li {
            display: table-cell;
        }

        .container .social_media ul li img {
            margin: 0 10px;
            height: 25px;
            width: auto;
        }
    </style>
</head>

<body>
    <div class="container" style="max-width:750px !important;">
        <a href="https://online.jkshahclasses.com" target="_blank"><img src="{{ $message->embed(public_path('logo1.png')) }}" alt="J K Shah Online" />
        </a>
        <div class="content">
            <h3>Dear {{ $attributes['fname']}}&nbsp;&nbsp;{{ $attributes['lname'] }},</h3>
            <h3 style="margin-bottom: 10px;">Your query has been received and is under process.<strong style="font-weight: 800;"></strong></h3>
            <h3>In case of any issue or query please Whatsapp us on 9757001272</h3>
            <h3>Thank You!</h3>


            <div class="social_media">
                <h6>© 2022 JKSHAH ONLINE, ALL RIGHTS RESERVED.</h6>
                <ul>
                    <li><a href="https://www.youtube.com/c/JKShahClassesOnline" target="_blank"><img src="{{ $message->embed(public_path('Youtube1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://www.facebook.com/officialjksc" target="_blank"><img src="{{ $message->embed(public_path('Facebook1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://www.instagram.com/officialjksc/" target="_blank"><img src="{{ $message->embed(public_path('Instagram1.png')) }}"
                                alt=""></a></li>
                    <li><a href="https://t.me/jkshahonline" target="_blank"><img src="{{ $message->embed(public_path('Telegram1.png')) }}" alt=""></a></li>
                </ul>
            </div>
        </div>
    </div>
</body>

</html>