<!doctype html>
<html>

<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Course Purchased</title>
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
            <h3>Hi {{ $attributes['to_name'] }},</h3>
            <h3 style="margin-bottom: 10px;">Seems like you forgot password for JKSHAH ONLINE. If this is true click below to reset your password.</h3>

            <h3><a href="{{ $attributes['url'] }}" style="background-color: #dd5f2b; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px;">Reset Password</a>
            </h3>
            <h3>If you didn't forget your password you can safely ignore this mail.</h3>
            <h3>Thanks TEAM JKSHAH ONLINE</h3>

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