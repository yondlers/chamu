<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Welcome to Chamu</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7fb;
            font-family: Arial, Helvetica, sans-serif;
            color: #263247;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        .wrapper {
            width: 100%;
            background-color: #f4f7fb;
            padding: 30px 12px;
        }

        .email-container {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(10, 46, 105, 0.08);
        }

        .content {
            padding: 38px 46px;
        }

        .logo {
            color: #08285f;
            font-size: 25px;
            font-weight: 700;
            letter-spacing: 8px;
            text-align: center;
            margin: 0 0 25px;
        }

        .logo-accent {
            color: #2fb7ad;
        }

        h1 {
            margin: 0 0 16px;
            color: #08285f;
            font-size: 32px;
            line-height: 1.25;
            text-align: center;
        }

        .intro {
            margin: 0 auto 26px;
            max-width: 520px;
            color: #536175;
            font-size: 17px;
            line-height: 1.7;
            text-align: center;
        }

        .feature-table {
            width: 100%;
            margin: 28px 0;
        }

        .feature {
            background-color: #f6f9ff;
            border: 1px solid #e5edf9;
            border-radius: 12px;
            padding: 18px;
        }

        .feature-title {
            margin: 0 0 6px;
            color: #08285f;
            font-size: 16px;
            font-weight: 700;
        }

        .feature-text {
            margin: 0;
            color: #657288;
            font-size: 14px;
            line-height: 1.55;
        }

        .button {
            display: inline-block;
            background-color: #1854c7;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 17px;
            font-weight: 700;
            padding: 15px 32px;
            border-radius: 9px;
        }

        .small-text {
            margin: 28px 0 0;
            color: #738095;
            font-size: 14px;
            line-height: 1.65;
            text-align: center;
        }

        .footer {
            padding: 26px 35px;
            background-color: #08285f;
            color: #d8e3f5;
            font-size: 13px;
            line-height: 1.6;
            text-align: center;
        }

        .footer a {
            color: #55d4c7;
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 12px 6px !important;
            }

            .content {
                padding: 30px 22px !important;
            }

            h1 {
                font-size: 27px !important;
            }

            .intro {
                font-size: 16px !important;
            }

            .feature-column {
                display: block !important;
                width: 100% !important;
                padding: 0 0 12px !important;
            }
        }
    </style>
</head>

<body>
    <table role="presentation" class="wrapper" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" class="email-container" width="100%">
                    <tr>
                        <td class="content">
                            <div class="logo">
                                CH<span class="logo-accent">A</span>MU
                            </div>

                            <h1>Welcome to Chamu, {{ $firstName }}!</h1>

                            @if ($isStudent)
                                <p class="intro">
                                    Your student account has been created successfully.
                                    You can now use Chamu to find bursaries, prepare your
                                    application documents, and keep track of funding
                                    applications from one place.
                                </p>
                            @else
                                <p class="intro">
                                    Your account has been created successfully.
                                    You can now use Chamu to understand your APS,
                                    discover qualifications you qualify for, explore
                                    South African universities, and find funding options
                                    for your next step after matric.
                                </p>
                            @endif

                            <table role="presentation" class="feature-table">
                                <tr>
                                    <td class="feature-column" width="33.33%" valign="top" style="padding-right: 6px;">
                                        <div class="feature">
                                            <p class="feature-title">{{ $isStudent ? 'Find bursaries' : 'Track your APS' }}</p>
                                            <p class="feature-text">
                                                {{ $isStudent ? 'Browse funding opportunities that match your study path.' : 'Enter your marks and see where you currently stand.' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="feature-column" width="33.33%" valign="top" style="padding: 0 6px;">
                                        <div class="feature">
                                            <p class="feature-title">{{ $isStudent ? 'Apply with Chamu' : 'Find qualifications' }}</p>
                                            <p class="feature-text">
                                                {{ $isStudent ? 'Upload your documents and let Chamu help prepare eligible applications.' : 'Discover qualifications that match your marks.' }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="feature-column" width="33.33%" valign="top" style="padding-left: 6px;">
                                        <div class="feature">
                                            <p class="feature-title">{{ $isStudent ? 'Track your history' : 'Explore funding' }}</p>
                                            <p class="feature-text">
                                                {{ $isStudent ? 'See sent applications, receipts, and postal-ready packs in your account.' : 'Compare bursaries early so money is part of the plan, not a surprise.' }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%">
                                <tr>
                                    <td align="center" style="padding: 8px 0 4px;">
                                        <a
                                            href="{{ $primaryActionUrl }}"
                                            target="_blank"
                                            class="button"
                                        >
                                            {{ $primaryActionLabel }} &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            @if ($isStudent)
                                <p class="small-text">
                                    Start by opening Funding and reviewing bursaries that match
                                    your field of study. If a bursary supports Chamu-managed
                                    applications, you can submit documents and keep the receipt
                                    trail in your account.
                                </p>
                            @else
                                <p class="small-text">
                                    Start by adding your school subjects and marks.
                                    Chamu will use them to calculate your APS, show
                                    you matching qualifications, and help you explore
                                    bursaries before applications get busy.
                                </p>
                            @endif

                            <p class="small-text">
                                Future first.<br>
                                <strong style="color: #08285f;">The Chamu Team</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="footer">
                            You received this email because an account was created
                            using this email address on Chamu.
                            <br><br>

                            <a href="{{ $homeUrl }}">Visit Chamu</a>
                            &nbsp;&bull;&nbsp;
                            <a href="mailto:support@chamu.co.za">Contact Support</a>

                            <br><br>
                            &copy; 2026 Chamu. Built for South African students.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
