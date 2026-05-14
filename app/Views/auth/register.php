<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hospital Registration - ABDM Gateway</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
             background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;
             display:flex;align-items:flex-start;justify-content:center;padding:30px 20px}
        .card{background:#fff;padding:40px;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);
              width:100%;max-width:680px;margin:auto}
        h1{font-size:22px;color:#1f2937;margin-bottom:4px;text-align:center}
        .brand-logo{display:block;max-width:220px;width:100%;height:auto;margin:0 auto 8px}
        .brand-divider{width:40px;height:3px;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:2px;margin:6px auto 10px}
        .sub{color:#6b7280;font-size:14px;text-align:center;margin-bottom:28px}
        .section-title{font-size:12px;font-weight:700;color:#764ba2;text-transform:uppercase;
                       letter-spacing:.8px;margin:20px 0 12px;padding-bottom:6px;
                       border-bottom:2px solid #f3f0ff}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-group{margin-bottom:16px}
        label{display:block;margin-bottom:6px;color:#374151;font-weight:600;font-size:13px}
        label span{color:#e53e3e;margin-left:2px}
        input[type=text],input[type=email],input[type=password],input[type=tel],select,textarea{
            width:100%;padding:10px 13px;border:1.5px solid #d1d5db;border-radius:8px;
            font-size:14px;transition:border-color .2s;font-family:inherit;background:#fff}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#764ba2;box-shadow:0 0 0 3px rgba(118,75,162,.1)}
        textarea{resize:vertical;min-height:75px}
        .hint{font-size:11px;color:#9ca3af;margin-top:3px}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:20px}
        .alert-danger{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
        .alert-success{background:#f0fdf4;border:1px solid #86efac;color:#166534;text-align:center;padding:30px}
        .alert-success i{font-size:48px;display:block;margin-bottom:12px}
        .btn{width:100%;padding:13px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
             border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:8px;
             transition:opacity .2s}
        .btn:hover{opacity:.9}
        .footer-link{text-align:center;margin-top:18px;font-size:13px;color:#6b7280}
        .footer-link a{color:#764ba2;font-weight:600;text-decoration:none}
        #pwMatch{font-size:11px;margin-top:3px}
        @media(max-width:600px){.row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="card">
    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAACSYAAAD6CAYAAAC2jAt/AAAACXBIWXMAAC4jAAAuIwF4pT92AAAgAElEQVR4nO3d23EbWbYt7Ll21CMiqGMBeSyQPCC3BdKxgCjAgEJbUFkWNMsAQKQFR2VBkxZsyYJDWfBLEXhf/wOTXapqXXhBYmYufF+Eorp1AQYligIWBkaWiKjBFP1ea11lh+D7lovtu4h4nZ1jAFfrzWyeHQIAxqqUchYR/8rOAfzFb7XWLjsEMKxSyklEnEXEl/89TooDT3UTEbcR8T4irmut73Pj7JfH0nAYaq1lH/dTSrmOiNN93Bc06mPcPS65//Y+It7XWm/TEo1cKWWUr73v6+vulJRS3kTEm7h77uh5Iy36HBHXEfEuIt7VWj9lBSmhmDRl/9s//OO1XGzPou1DlP+93sxus0MAwBh5MQVGSTEJGuUwmQMwmsPkffBYGg6DYhJM3se4e3xyHQfw+OQxFJPG7Yvnj28i4ig5DuzbH3H3Nfty33f8X/u+Q3aqyw7Ad3XZAQbWZQcAAADgMJVS3pRSLkspnyLi/0bEeSgl0a6juFvkfhsR/18p5V0pZZ4bCQA4cMdx9xj8/vHJ+1LKvJTyIjkX/IdSyov+8/M2/nz+qJTEIXodEW9LKZ9KKd0+v2YrJk3beT9Rzsj0a0mtvwvjfLnYvsoOAQAAwGFwmAz/lnaYDADwDS/jrqR027+B4CQ3DtwppXRxdxnCt+HNLHDvKCJ+jbuv2Xt5TqmYNH2X2QH4qovsAHtyKB8nAAAAiRwmw1ft/TAZAOAHjuLuDQT/r5Ry3V+iFfbuize1/Bre0ALf8uVzytWQd6SYNH2n/lEfl+ViO4+7ZvghOO3XoQAAAGDnHCbDg+ztMBkA4BFOI+JfFpTYp1LKSSnlOrypBR7jKCL+2V+W82yIO1BMakOXHYC/6LID7FmXHQAAAIC2OEyGJxn8MBkA4AnuF5S67CC0rS/pv4+7UhzweC/jrlB6setFXsWkNlhNGol+LenQDkxPl4vtm+wQAAAAtMFhMjzbYIfJAADP8Gsp5baU8io7CG0ppbzo39jyz7C0C7vwS0Rc7/LrtWJSOy6yAxy65WL7Ig73z+FQP24AAAB2xGEy7NzOD5MBAJ7pOCL+x3oSu9I/1r0Nb2yBXXsZd88n57u4McWkdrzc1ScFT7aKwz04Pe7XogAAAODRHCbDYHZ6mAwAsCO/llKurTvyHP1j3P+Jw319FoZ2FBFvSynPHilRTGpLlx3gUPVrSavsHMm67AAAAABMj8NkGNzODpMBAHboNCJc2o0n6Z9Hvs3OAQfil1LK5XNuQDGpLcfe/ZTmkNeS7h0vF9suOwQAAADT4TAZ9urZh8kAADt2FHfrjm+ygzAd/WNazyNhv86f83xSMak9ndnD/VouticR8Wt2jpFY9etRAAAA8F0OkyHFsw6TAQAGcBQR/9f4Ag/Rr4CeZ+eAA/Xk55OKSe05DpcU27cuO8CIHIXPPwAAAH7AYTKkUk4CAMborXIS39N/fvySnQMO3JOeTyomtWllNWk/+rUkB6l/ZTUJAACAb3KYDKOgnAQAjJFyEl/lMuAwKuf9G84eTDGpTVZr9udRf+EOxFH4fQEAAOArHCbDqDz6MBkAYA/ellLOskM8w+fsAK0ppbwKrz3C2PzymCKpYlK7VqWUk+wQLVsutmcR8To7x0id92tSAAAAEBEOk2GkHnWYDACwJ+/65w9T9D47QEv6qwS9i7thBGBcLh76tVoxqV1HEdFlh2hclx1g5LrsAAAAAIyDw2QYtQcfJgMA7MlR3JWTXmQHId1lRBxnhwC+6sFfqxWT2nZuNWkY/VrSaXaOkTtfLrYOtQAAAIhwmAxj5oU/AGCMjuPueQQHqpSyClevgbE7jgcMligmta/LDtAo0/MP4/cJAADgwDlMhkl40GEyAMCeve6fT3Bg+vGNLjcF8EC/lFLOvvcTFJPad26KebeWi+08Il5m55iI035dCgAAgAPkMBkm5YeHyQAACTpXiDlIF+FS4DAll9/7QcWkw2C1Zre67AAT02UHAAAAII3DZJiWy+wAAAB/cxQeoxyUvixvdRem5biU0n3rBxWTDsOpdzvtRr+WdJydY2JOl4vtm+wQAAAA7JfDZJik7x4mAwAkOS2lzLNDsDeX2QGAJ1mVUl587QcUkw5Hlx1g6paL7YuwPvVUft8AAAAOz2V2AOBJvnmYDACQ6MJjlPb1BTQjETBNR/GNXopi0uE4LaVYrXmeVZiff6rjfm0KAACAA+AwGSbtm4fJAACJjuLutTra1mUHAJ5l/rUSqWLSYbFa80T9WpIHO8/TZQcAAABgb7rsAMCzfPUwGQAgmWXHhnmDCzThqyVSxaTDcuz6q09mLen5jpeLrXIXAABA4xwmQxMsEgAAY+QxStvm2QGAnVBMwjsWH2u52J6EBzm70vXrUwAAALRrnh0A2AnnYQDAGHmM0qBSyklEnCbHAHbj6O+DOYpJh+e4lOIf7MfpwlrSrmiyAwAANMxhMjTlPw6TAQBGwGOUNnn9ENry5sv/o5h0mDrXX32Yfi3pPDtHY1ZWkwAAAJrlMBna8ubHPwUAYO8872iPx53QltdfdlIUkw6T1ZqH67IDNOgo/L4CAAC0ymEytOW1NzgCACP0sl9rpQGllFcRcZydA9i5f58RKSYdrpVDhe9bLrZnYS1pKL/0a1QAAAA0wmEyNEvhEAAYI49R2uHPEtqkmITVpAfosgM0rssOAAAAwE45TIY2+bsNAIzRPDsAO3OWHQAYxNn9/1BMOmy/mjn8un4t6TQ7R+POrSYBAAA05Sw7ADCIs+wAAABf8dLVYZrhNVlo01G/rq2YhNWab+iyAxyIy+wAAAAA7IzDZGjTvw+TAQBG5iw7AM9TSjnLzgAMSjGJiIg4t5r0V8vFdh4OU/fltF+nAgAAYMIcJkPzFJMAgDE6yw7As3mcCW17FRHxU3YKRuEiXCv+S112gAPThQeOAAAAU+cwGdrm7zgAMEYeo0zfSXaAR/gcEdcR8T45B+Nw23/bp7OIeBF33Y7jPd/3Uykm8W+vSylntdbr7CDZ+rWkqfwlbsXpcrE9W29m19lBAOBA/JYdgFH7FO0ertxmB4DGnWQHeASHyXzpNhwmP8Q+X/S7DY9Zp+LX7ADfMLXPn5Yfg/M0H8PzF/K8iIiX2SEewRVQpm8K5bLPEbGqtV5mB+HgXff/XfXL1Zcx/ueUJxERJSJqbg5G4qbWepYdItNysX0Rd08Ax/6Xt0Uf15vZSXYIANiV/knBv7JzfE2ttWRnAKA9pZTrGP+LAg6TGZ0JHSZ/rLWeZIdgXEopo3xtwXMevjSRxygRd2WkLiLe1Vo/JWfhwJVSTiJiHhGriDjKzPJA/2tMf2/G+nVnrP8+jvX36wsfIuJsTJ9jcK+U8iLunk++To7yXbXW8l/ZIRiN0/4g5JCtYvyHQK067teqAKAJligBYHQ+RMSJUhJj0z9ufBURfyRH+RFnZgDDuYqIV7XWSy98Mwa11ttaaxd3KxcfctM8yBQWd/g2pSR4ov5zcx4T+FqtmMSXuuwAWfq1pFV2jgPXZQcAAADgyRwmwxNN6TAZgJ37R6117nEKY9R/Xp7F+B+jvMgOQJM+R8QbX58Zu/5z9E12ju8ppZwpJvGl01LKPDtEkqnMUbbseLnYKocBAACwSw6TmYSpHCZnZwBozFWt9SI7BHzPF+Wkz8lRvsdiEkO4qLXeZoeAh+g/V6+yc3yPYhJ/12UH2LflYnsS1pLGouvXqwAAAGAXHCYzGVM4TAZgZz6H1yWYiL6cpETHofE5z9SM+nNWMYm/Oz7A1aQurCWNxVF4MgYAAMDujPpgDr7C5yzAYbiw6MjEeIzCIfngazRTU2t9n53hexST+JqulHIQqzX9WtJ5dg7+YmU1CQAAgB1wmMzkjP0wGYCdeZcdAB6jf1x9k50D9sTzSKbqY3aAb1FM4muO43BWa7rsAPyHo/DnAgAAwPM5TGaqRnuYDMBuKKIyUT5vAcbtNjvAtygm8S2r1leTlovtWVhLGqtf+jUrAAAAgENzmx0AAOArFP8BeBLFJL7lKNpfTeqyA/BdXXYAAAAAAAAAAODpFJP4nmZXk/q1pNPsHHzXudUkAAAAAAAAAJguxSS+5ygiLrJDDKTLDsCDXGYHAAAAAAAAAACe5qeI+O/sEIxac9eLXS6282hzLelDRLzMDrFjp8vF9my9mV1nBwEAAAAAAAAAHuenWut1dgjYsy47wAA+RsRZRNzG3dJVS7q4+9gAAAAAAAAAgAlxKTcOSr+WdJydYwDdejP7FBGr7CADOF0utmfZIQAAAAAAAACAx1FM4mAsF9sX0eZa0of1ZnYZEdH/92NqmmFcZgcAAAAAAAAAAB5HMYlDsoo215L+vpLUZYQY2HG/dgUAAAAAAAAATIRiEgehX0tq8TJnN+vN7PrL7+hXkz6kpBlWlx0AAAAAAAAAAHg4xSQOxSoijrJDDKD7xve3WMI6Xi62LX5cAAAAAAAAANAkxSSat1xsT6LNos4ff19Lutd//81e0+xH169fAQAAAAAAAAAjp5jEIeiizbWkH5Wtun2E2LOjaLNkBgAAAAAAAADNUUyiaf1a0nl2jgFcrTez2+/9hH416Y+9pNmvldUkAAAAAAAAABg/xSRa12UHGEj3wJ/X4rrQUbT75woAAAAAAAAAzVBMolnLxfYs2lxL+u1Ha0n3+p93NWiaHL/0a1gAAAAAAAAAwEgpJtGyLjvAAD5HxMUjf003QI4x6LIDAAAAAAAAAADfpphEk/q1pNPsHAO4WG9mnx7zC/rVpN+HiZPq3GoSAAAAAAAAAIyXYhKt6rIDDOApa0n3uv7Xt+YyOwAAAAAAAAAA8HWKSTRnudjOo821pNVj15Lu9b/uqaWmMTvt17EAAAAAAAAAgJFRTKJFXXaAAXxcb2aXz7yNi2hzNanLDgAAAAAAAAAA/CfFJJrSryUdZ+cYQPfcG+hXk559OyNkNQkAAAAAAAAARkgxiWYsF9sX0Wbx5sMO1pIiImK9mV1ExMdd3NbIXGYHAAAAAAAAAAD+SjGJlqyizbWk1Y5vr9vx7Y3Bcb+WBQAAAAAAAACMhGISTejXknZd4BmDm/Vmdr3LG+zXl1pcTeqyAwAAAAAAAAAAf1JMohWriDjKDjGAbqDbnQ90u5mOl4tti+U0AAAAAAAAAJgkxSQmb7nYnkSba0l/7Hot6V5/uzdD3Hayrl/PAgAAAAAAAACSKSbRgi7aXEsaumzVDXz7GY6izZIaAAAAAAAAAEyOYhKT1q8lnWfnGMDVejO7HfIOGl5NWllNAgAAAAAAAIB8iklMXZcdYCDdnu5nvqf72aejaPfzAgAAAAAAAAAmQzGJyVoutq+izbWk34ZeS7rX38/VPu5rz37p17QAAAAAAAAAgCSKSUzZRXaAAXyO/X9c3Z7vb1+67AAAAAAAwH6VUs6yMwAAAH9STGKSlovtWUScZucYwMV6M/u0zzvsV5N+3+d97sm51SQAAAAAAAAAyKOYxFR12QEGkLGWdK/r7781l9kBAAAAAAAAAOBQKSYxOcvF9k20uZa02vda0r3+flu8NN5pv64FAAAAAAAAAOyZYhJT1GKB5uN6M7tMznARba4mddkBAAAAAAAAAOAQKSYxKcvFdh4Rx9k5BtBlB+hXk9JzDMBqEgAAAAAAAAAkUExiarrsAAP4MIK1pIiIWG9mFxHxMTvHAFpc2QIAAAAAAACAUVNMYjKWi20Xba4lrbID/E2XHWAAL/u1LQAAAAAAAABgTxSTmITlYvsixlfg2YWb9WZ2nR3iS/16U4urSV12AAAAAAAAAAA4JIpJTMUqIo6yQwygyw7wDfPsAAM4tpoEAAAAAAAAAPujmMToWUvavz7XTXaOAVz0n08AAAAAAAAAwMAUk5iCi2hzLWmeHeAHuuwAAziKNktuAAAAAAAAADA6ikmM2nKxPYmI8+wcA7hab2a32SG+p+HVpJXVJAAAAAAAAAAYnmISY9dlBxhIlx3ggebZAQZgNQkAAAAAAAAA9kAxidFaLravos21pN/HvpZ0r895lZ1jAL/2a1wAAAAAAAAAwEAUkxizi+wAA/gc01lLutdlBxhIlx0AAAAAAAAAAFqmmMQoLRfbs4g4zc4xgIv1ZvYpO8RjNLyadG41CQAAAAAAAACGo5jEWHXZAQbwOaa7ArWKu/ytmeqfBwAAAAAAAACMnmISo7NcbN9Em2tJ3dTWku71uVss8bzu17kAAAAAAAAAgB1TTGKMWizAfFxvZlP/uC6izdWkLjsAAAAAAAAAALRIMYlRWS6284g4zs4xgC47wHM1vJp0ajUJAAAAAAAAAHZPMYmx6bIDDODjejO7zA6xC+vNrIuIj9k5BtBi4QoAAAAAAAAAUikmMRrLxbaLNteS5tkBdqzLDjCAl/1aFwAAAAAAAACwIz8tF9uaHQIadrPezK6zQ+zSejO7bLRE1kXEZXIGAAAAAAAAAGiGxSQYVpcdYCCr7AADOLaaBAAAAAAAAAC7o5gEw2luLeneejN7FxE32TkGcLFcbF9khwAAAAAAAACAFigmwXDm2QEG1mUHGMBRtLkGBQAAAAAAAAB7p5gEw7hab2a32SGG1K9BtbiatLKaBAAAAAAAAADP91N2AGhUlx1gT1YR8T/ZIXbsfjWpS84BAIMopZxlZ9iBVxGhSDxitdYuOwMAAAAAAPkUk2D3fm99LeneejN7v1xsryLiPDvLjv26XGwvD+XPEYCD86/sAByELjsAAAAAAAD5XMoNdutzHN6LMF12gIF02QEAAAAAAAAAYMoUk2C3Ltab2afsEPvUrwpdZecYwPlysT3JDgEAAAAAAAAAU6WYBLvzOSIuskMkWcXdx9+aQ/3zBAAAAAAAAIBnU0yC3ekObS3pXv9xt1jieb1cbM+yQwAAAAAAAADAFCkmwW58XG9mLRZzHuMi2lxN6rIDAAAAAAAAAMAUKSbBbnTZAbI1vJp0ajUJAAAAAAAAAB5PMQme7+N6M7vMDjEG682si4iP2TkG0GLhCgAAAAAAAAAGpZgEzzfPDjAyXXaAAbxcLrbz7BAAAAAAAAAAMCWKSfA8N+vN7Do7xJj061EtriZ12QEAAAAAAJi+UsqL7AzwBD5vAXgSxSR4ni47wEitsgMM4NhqEgAAAAAAO3CWHQCe4Cw7AADTpJgET2ct6RvWm9m7iLjJzjGAi+Vi6x0BAAAAAAA8x5vsAPAYpZSTiHiZHAOAiVJMgqebZwcYuS47wACOos01KAAAAAAA9ue8lPIqOwQ8wkV2AACmSzEJnuZqvZndZocYs35NqsXVpJXVJAAAAAAAnumylOKsmdErpcwj4nV2DgCmSzEJnqbLDjARLa4LWU0CAAAAAOC5XkbEtXISY9aXkt5m5/iB2+wAAHyfYhI83u/Wkh5mvZm9j4ir7BwD+HW52J5khwAAAAAAYNJeRsRtX/6A0SilnJRS3sX4S0kRikkAo/dTdgCYmM9hLemxuog4zw4xgC4i5skZAAAAAACYtqOIeFtK6SLiXdyVLN5nBpqIk/4bu/UiIs7irjQ3FbfZAQD4PsUkeJyL9Wb2KTvElKw3s9vlYnsV7ZWTzpeLbWc9CwAAAACAHTiOiF+yQ8DU1FpvszPQnBellLPsEPAEo708rGISPNzniLjIDjFRXUS8ibt3frTkIu4+LgAAAMbHYTJTNdrDZACAkbnJDkCTXkbEv7JDQEsUk+DhOmtJT9OvJl1ExK/ZWXbs9XKxPVtvZtfZQQAAAPgPDpMBAKBtLnsIMAH/lR0AJuLjejOzlvQ8F3G3OtWaLjsAAAAAAADAAbrODgDAjykmwcN02QGmrl+barHcdbpcbM+yQwAAAAAAAByY6+wAAPyYS7nBj31cb2aX2SEacRER84g4Ts6xaxcR8So7BAAA01JK8TiSh7qstV5mhwAAABiRD7XWT9khAPgxxST4sXl2gFasN7NPy8W2i4i32Vl27OVysZ0rsAEA8EivIuI0OwSTcJ0dAAAAYGTeZQcA4GFcyg2+72a9mV1nh2hJX975mJ1jAF12AAAAAAAAgANxmR0AgIdRTILv67IDNKrLDjCA4+ViO88OAQAAAAAA0LgPtdbb7BAAPIxiEnybtaSB9KtJN9k5BnCxXGxfZIcAAAAAAABo2EV2AAAeTjEJvm2eHaBxXXaAARxFxCo7BAAAAAAAQKM+R8S77BAAPJxiEnzd1Xozu80O0bJ+jarF1aSV1SQAAAAAAIBBXNRaP2WHAODhFJPg67rsAAeiyw4wAKtJAAAAAAAAu/c5XMYNYHIUk+A//W4taT/61aSr7BwDWC0X25PsEAAAAAAAAA2xlgQwQYpJ8Fefo80VnzHrsgMM4Cja/LgAAAAAAAAyWEsCmCjFJPiri/Vmpmm9R/06VYurSedWkwAAAAAAAHais5YEME2KSfAnTes8Xdz9/remyw4AAAAAAAAwcTe1Vq/hAUyUYhL8yVpSkn41qcUHlOfLxfYsOwQAAAAAAMCErbIDAPB0iklw5+N6M+uyQxy4i7CaBAAAAAAAwJ/+UWt9nx0CgKdTTII7XXaAQ9evVbW4mnRqNQkAAAAAAODR/nAJN4DpU0yCu7Wky+wQRMRdMeljdogBdNkBAAAAAAAAJuRjRMyzQwDwfD9lB4ARcF3akVhvZp+Wi20XEW+zs+zY6XKxnSvAATAGtdaSnQEAAAAAvuNzRLyptX7KDgLA81lM4tDdrDezd9kh+FNf3rGaBAAAAAAAcJje1FrfZ4cAYDcUkzh0XXYAvqrLDjCA4+ViO88OAQAAAAAAMGI/11qvs0MAsDuKSRyym/Vmdp0dgv/UrybdZOcYQLdcbF9khwAAAAAAABihn2utl9khANgtxSQO2So7AN/VZQcYwHH4vAMAAAAAAPg7pSSARikmcaiu1puZa9OOWL9m1eJq0spqEgAAAAAAQEREfI6I/1ZKAmjXTxHx39khIEFzpaRSyrta65vsHDu2ioj/yQ6xY0dx93F1yTn4huViex0R8/VmdpscBQAAAAAAWvYhIua11uZetwPgTz/1qyTAhJVS5hHxupQyb6lRvt7M3i8X26uIOM/OsmOr5WJ7qfgyPsvF9iwiTuOuODbPzAIAAAAAAA37I+5KSZ+ygwAwLJdyg4krpbyIiIv+/3aJUYbSZQcYwFG0+XG1oOv/e75cbE8ScwAAAAAAQIs+R8TPtdY3SkkAh0ExCaZvFXdFl4iI4349qRn9qtBVdo4BKL6MzBdrSfcuc5IAAAAAAECTbiLiVUtX/wDgxxSTYML6taTV3777ov/+lqzirkHfmi47AH/R/e3/n/ZlJQAAAAAA4Ok+RsT/qbWe1Vpvs8MAsF+KSTBtX64l3TuK/ywrTdp6M/sUf16uriXnii/j8JW1pHvdfpMAAAAAAEAzPkfEb7XWk1rru+wwAORQTIKJKqWcRMSv3/jhVYOrSRdhNYnhdN/4fqtJAAAAAADwOB8j4reIOKm1dslZAEimmATT1X3nx6wmTYfiS7LvrCXdu9xPEgAAAAAAmLSbiPi5X0jqaq2fsgMBkE8xCSaoX0s6/8FP+7X/eS25iLuWfWu67AAHrvvBjx8vF9v5HnIAAAAAAMDUfIiIf0TE/661ntVaL5PzADAyikkwTQ9dDuqGDLFv/WpSl51jAKeKLzkesJZ0rxs2CQAAAAAATMJNRPweEf8nIv5XrfVVrfWi1nqbGwuAsfopOwDwOKWUs4h4/cCffl5K6Vp6MLjezC6Xi20XEcfZWXasC5cMyzB/4M87Xi62q/Vm1uLlBAEAAAAAGL8PEbGvS6Pd9t++/N+3Lb3eBMD+KCbB9HSP/PkXEfFmgByZuoh4mx1ix46Xi+18vZldZgc5FMvF9iR+fEnEL3XLxfayX+4CAAAAAHiq//7BjyuA7Fgp5X1EvMzO8Uy3tdbWXu+BMfoYxgSYpnmMdNxDMQkmpF9Leshlp770upRyVmu93n2iHP1q0jwe/3sxdt1ysX2n+LI33SN//lFErJ7w6wAAgBwOk5mqeYz0MBmA3WjpvH5CziLifUz739jXpZSLWusqOwg07rbW2mWHgMfquwSj/HdOMQmm5amXkeri7kF3S7qI+Fd2iB07DsWXvXjCWtK91XKxvVAeAwCASXCYzCSN+TAZAKaq1vqplPImIq7j7k2oU/VLKeV9rfUyOwgAPNR/ZQcAHqaUMo+nz4ye9odazVhvZtcRcZOdYwCr5WL7IjvEAeie+OuO4ukFQQAAAAAAktRa38fdMuHUvW3tNR8A2qaYBNPRPfPXt1im6LIDDOD+cmEM5BlrSffO+9sAAAAAAGBCaq3vIuIf2Tl24F0p5VV2CAB4CMUkmIB+Lem5E94v+9tpRr+a9Ed2jgGsFF8G1Y3kNgCAA1drPau1Ft98e8C3LvvzFQAAWlFrvYiIq+wcz3QUEZelFFdgAGD0FJNg5PoHlbtaO+p2dDtj0uK60FG0+WeVbgdrSffOl4utd6MAAAAAAExQrXUeER+yczzTy4i4zg4BAD+imATjt4q7osouHDe4mnQb039nw9e4XNgwuh3eVouXRwQAAAAAOBRnEfExO8QzvSylXGaHAIDvUUyCEevXkna9CHTR4LRnlx1gIF12gJbscC3p3ulysT3b4e0BAAAAALAntdZPEfEmIj5nZ3mm81JKi1eXAKARikkwbrtcS7p3FI1d/qxfTfo9O8cAzhVfdqqbyG0CAAAAALAHtdb3cVdOmrp/llJa+DgAaJBiEoxUKeUkIn4d6OZXja4mTf1dDV/TZQdowQBrSfdOl4utJ3sAAAAAABNVa72OiJ+zc+zAZSnlVXYIAPg7xSQYr27A225xNelTRFxk5xiAy4XtRjfgbbf4eQcAAAAAcDBqrZcRcZWd45mOIuK6wTemAzBxikkwQv1a0hDrLl/6tb+fllyE1ST+ZrnYvohhp3iPl4vtfMDbBwAAAABgYLXWeUTcZKYLuAUAAA5hSURBVOd4JuUkAEZHMQnGaV8LLN2e7mcv+tWkLjvHAE4VX55lFXdPxobU9QUoAAAAAACm601EfMgO8Uwvw9I/ACOimAQjU0o5i4jXe7q789ZWk9ab2UVEfMzOMYAuO8AU9WWhfVy28HhP9wMAAAAAwEBqrZ8iYh7TvzrDeSlFOQmAUVBMgvHpGr+/feiyAwzA5cKeZh9rSf++L6tJAAAAAADTVmt9H3fLSVP3Syllnh0CABSTYET6taTTPd/teX+/zVhvZpfR6GqS4svD7XEt6d7Rnu8PAAAAAIAB1FqvI+Ln7Bw78LaU8io7BACHTTEJxiVrVrNLut8htVgQcbmwx9nnWtK/73O52J7s+T4BAAAAANixWutlRFxl59iBa+UkADIpJsFI9HOaL5Pu/rTB1aR3EXGTnWMALhf2AAlrSfeOos2iHwAAAADAwam1ziPij+wcz3QUEZelFK8tAJBCMQnGozvw+x9Clx1gAC4X9jAZa0n3zq0mAQAAAAA0Yx4RH7JDPNPLiHiXHQKAw6SYBCPQryUdJ8c47XM0Y72ZXUe7q0kn2SHGKnEt6Utd8v0DAAAAALADtdZPEfEmIj5nZ3mm01LKZXYIAA6PYhIk66czL7Jz9LrsAAPILqgMweXCvi9zLene+XKxPUvOAAAAAADADtRabyPiLDnGLpyXUlp83QSAEVNMgnxjKFHcO25wNel9RFxl5xiAy4V9xUjWku512QEAAAAAANiNWuv7iPg5O8cO/LOU8iY7BACHQzEJEvVrSWMpUdzr+lwt6bIDDKTLDjBCYyr6nVpNAgAAAABoR631MiJ+z86xA5ellFfZIQA4DIpJkGtMJYp7xzG+stSzrDez22h3NeksO8TIjO1zdyyXaQQAAAAAYAdqrauI+CM7xzMdRcS7Bt+oDsAIKSZBklLKSUT8mhzjW1YNPhjtIuJzdogBdNkBxmK52M5jfEW/l30uAAAAAADaMY+ID9khnuk4Iq4bfD0IgJFRTII8XXaA7ziK8S3PPEu/mtTieo3Lhf2pyw7wDV12AAAAAAAAdqfW+iki3sT03xD9Mtp87QSAEVFMggT9WtJ5cowfWfU5W3IR03+S8DVddoBs/SrRcXaObzi2mgQAAAAA0JZa621EnMX0X3c4L6V02SEAaJdiEuSYQvv8KBorvKw3s08xjd/7xzpdLrZvskMk67ID/MDFcrE1hwsAAAAA0JBa6/to4woUv5ZS5tkhAGiTYhLsWSnlLCJeZ+d4oHOrSZPRYuHqQUa+lnSvucsjAgAAAAAQUWu9jIjfsnPswNtSyqvsEAC0RzEJ9q/LDvBIXXaAXepXk1osiBzy5cK67AAPtLKaBAAAAADQnlprFxFX2Tl24LrBN6wDkEwxCfaoX0s6zc7xSOd97masN7PLiPiYnWMAXXaAfZvIWtK95i6PCAAAAADAv60i4kN2iGc6ioh3pRRvsgVgZxSTYL+mermtLjvAALrsAAM4Xi62XXaIPeuyAzzSL8vF9iQ7BAAAAAAAu1Vr/RQRZxHxOTnKc72MiHfZIQBoh2IS7EkpZR53D+am6LTR1aSpv3Phaw7mcmETW0v6UpcdAAAAAACA3WuonHRaSrnMDgFAGxSTYH+67ADP1GUHGMAqO8AAjqLNj+truuwAT3RuNQkAAAAAoE211vfRxjn9ef+mewB4FsUk2IP+gdsUl12+dNraA9D1ZnYdETfZOQbQ/GrShNeS7l1mBwAAAAAAYBi11suI+C07xw68be2KGgDsn2ISDKyU8iIiLrJz7EiXHWAAXXaAARxFO59z39JlB3im0+Vie5YdAgAAAACAYdRau4i4ys6xA+9KKa+yQwAwXYpJMLxV3BVFWnBsNWkymr1c2HKxfRPTXku612UHAAAAAABgUKuI+JAd4pmO4q6c1PSVGgAYjmISDKh/kNbCdYS/1DX44HOeHWAgXXaAgbTyd8pqEgAAAABAw2qtnyLiLCI+Jkd5ruOIuM4OAcAknfxUSqnZKYBJOY6IFxHxKTvIrqw3s9vlYnsVEefZWXbsfLnYtvYxteZVeDIHAADANJxkBwCAKaq1fiqlvIm7s+ApX2HjZSnlstY6zw4CwKScWEwCHuuq1nqbHWIAXXYADs7niLjMDgEAAAAPdJIdAACmqtb6Ptq4esN5KaXLDgHAtCgmAY/VZQcYwnozu42Iq+wcHJSL9WbWzPIYAAAAAADfVmt9FxH/yM6xA7+WUubZIQCYDsUk4DFaXUu6t4q7FRsY2ueIuMgOAQAAAADA/tRaL6KNN0lflFJeZYcAYBoUk4CH+hx3xZ1m9es1yiLsg7UkAAAAAIADVGudR8SH7BzPdBQR16WUk+QcAEyAYhLwUBe11kMoUlyE1SSGZS0JAAAAAOCwnUXEx+wQz3QUEe9KKS+ygwAwbopJwEMcTJHCahJ7YC0JAAAAAOCA9W8EfxPTf6P0y4i4zA4BwLgpJgEPcShrSRERsd7Mupj+OxUYp4Mp+QEAAAAA8G211vcRMc/OsQOvSynOvQH4JsUk4Ec+xmEWKbrsADTJWhIAAAAAABERUWt9FxE/Z+fYgV9KKfPsEACMk2IS8CPdIa0l3VtvZpdhNYndspYEAAAAAMBf1FovI+IqO8cOvC2lnGWHAGB8FJOA7/nYPyA+VF12AJpiLQkAAAAAgP9Qa51HxE12jh14V0p5lR0CgHFRTAK+p8sOkKlfTWrhiQD5rCUBAAAAAPA9byLiQ3aIZzqKiMtSyovsIACMx0/ZAYDRujnwtaR7XUT8KzsEk2ctCRiNUsp1dgZIcunxLQAAAGNVa/1USplHxHXcFXym6mXcfQyWkwCICMUk4Nu67ABjsN7MrpeL7U1EnGZnYdKsJQFj4t80DtV1dgAAAAD4nlrr+1LKm5j+G6ZfllIu+0vUAXDgXMoN+JqbWut1dogR6bIDMGlX1pIAAAAAAHiI/vWZn7Nz7MB5KWWVHQKAfIpJwNd02QHGZL2ZXUfEVXYOJqvLDgAAAAAAwHT0lyJv4XWJf/aXpwPggCkmAX93ZS3pq7rsAEzS1Xozu80OAQAAAADAtPSXQbvJzrEDF6WUV9khAMijmAT8XZcdYIz6ckkL705gv7rsAAAAAAAATNabiPiQHeKZjiLiupTyIjsIADkUk4AvXdVab7NDjFiXHYBJsZYEAAAAAMCT1Vo/xV056XN2lmdSTgI4YIpJwL3PoXjzXX3J5LfsHExGlx0AAAAAAIBp699QfpYcYxdeRsRldggA9k8xCbh3YS3pQS5i+u9MYHjWkgAAAAAA2Ila6/uI+Dk7xw68LqVcZIcAYL8Uk4CIu6KNB4IPsN7MPoXfK36syw4AAAAAAEA7aq2XEfF7do4d+KWUMs8OAcD+KCYBEXdrSZ+yQ0yI1SS+x1oSAAAAAAA7V2tdRcQf2Tl24G0p5VV2CAD2QzEJ+BgWgB6lX03qsnMwWl12AAAAAAAAmjWPiA/ZIXbgWjkJ4DAoJgGdtaTHW29mF3FX6oIvWUsCAAAAAGAw/Ws6b2L6V3Y4iojLUsqL7CAADEsxCQ7bx/6axDxNlx2A0emyAwAAAAAA0LZa621EnCXH2IWXEfEuOwQAw1JMgsPWZQeYsvVmdhlWk/iTtSQAAAAAAPai1vo+In7OzrEDp6WUy+wQAAxHMQkO1421pJ2YZwdgNLrsAAAAAAAAHI7+dZ7fs3PswHkpZZUdAoBhKCbB4eqyA7RgvZldR8RNdg7SWUsCAAAAAGDvaq2riLjKzrED/yylvMkOAcDuKSbBYbqptV5nh2hIlx2AdF12AAAAAAAADtYqIj5kh9iBy1LKq+wQAOyWYhIcpi47QEusJh08a0kAAAAAAKSptX6KiLOI+Jwc5bmOIuK6lPIiOwgAu6OYBIfnylrSIFz7+HB12QEAAAAAADhsykkAjJViEhyeLjtAi9ab2fto4xrOPI61JAAAAAAARqHW+j7aeCP1y4i4yA4BwG4oJsFhuaq13maHaFiXHYC988QIAAAAAIDRqLVeRsRv2Tl24LyU0mWHAOD5FJPgsHTZAVrWL+dYTTocN/1SFgAAAAAAjEattYs2Xq/4tZQyzw4BwPMoJsHh+M1a0l6sYvrXb+ZhuuwAAAAAAADwDauI+JAdYgfellJeZYcA4OkUk+AwfA6XnNqL9Wb2KfxeH4Kb9WZ2nR0CAAAAAAC+ptb6KSLOoo03U1+XUk6yQwDwNIpJcBgu+geg7MdFtPFAn2/rsgMAAAAAAMD3NFROOoqId6WUF9lBAHg8xSRon7WkPbOa1DxrSQAAAAAATEKt9X1EzLNz7MDLiHiXHQKAx1NMgvatrCWluIiIj9khGESXHQAAAAAAAB6q1vouIv6RnWMHTkspl9khAHgcxSRo28da62V2iEPUryZ12TnYOWtJAAAAAABMTq31IiKusnPswHkpZZ4dAoCHU0yCtnXZAQ7ZejO7DKtJremyAwAAAAAAwFPUWucR8SE7xw68LaW8yQ4BwMMoJkG7PlhLGoUuOwA7Yy0JAAAAAICpO4s23lR9WUo5yQ4BwI8pJkG7VtkB+PdqUgvvPkDJDAAAAACAiau1foqINxHxOTvLMx1FxEl2CAB+TDEJ2nRTa73ODsG/KYlNn7UkAAAAAACaUGt9HxHz7BwAHAbFJGhTlx2AP/WFlpvsHDxLlx0AAAAAAAB2pdb6LiL+kZ0DgPYpJkF7/rCWNEpddgCezFoSAAAAAADNqbVeRMRVdg4A2qaYBO1x2bAR6ostf2Tn4Em67AAAAAAAADCEWus8XPUBgAEpJkFbrmqtt9kh+CalsemxlgQAAADwdV7EBmjHm4j4kB0CgDb9lB0A2KkuOwDftt7MbpeL7VVEnGdn4cG67AAAAAAAADCkWuunUso8Iq4j4ig3zbTVWs+yM0xJrbVkZ4BWjPnrz/8Pj2AKpPhhQrQAAAAASUVORK5CYII=" alt="E-Atria" class="brand-logo">
    <div class="brand-divider"></div>
    <h1>ABDM Gateway</h1>
    <p class="sub">Hospital Registration Request</p>

    <?php if (!empty($submitted)): ?>
    <div class="alert alert-success">
        <i>✅</i>
        <strong>Application Submitted Successfully!</strong><br><br>
        Your registration request has been received and is pending admin review.<br>
        You will be notified at your contact email once the account is activated.<br><br>
        <a href="/" style="color:#166534;font-weight:600;">← Back to Login</a>
    </div>

    <?php else: ?>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">⚠ <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/register">
        <?= csrf_field() ?>

        <!-- Hospital Info -->
        <div class="section-title">🏥 Hospital Information</div>
        <div class="form-group">
            <label>Hospital Name <span>*</span></label>
            <input type="text" name="hospital_name" value="<?= esc(old('hospital_name')) ?>" required placeholder="e.g. City General Hospital">
        </div>
        <div class="row">
            <div class="form-group">
                <label>HFR ID (Health Facility Registry)</label>
                <input type="text" name="hfr_id" value="<?= esc(old('hfr_id')) ?>" placeholder="e.g. IN2600XXX">
                <div class="hint">Leave blank if not yet registered with HFR.</div>
            </div>
            <div class="form-group">
                <label>State <span>*</span></label>
                <select name="state" required>
                    <option value="">-- Select State --</option>
                    <?php
                    $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
                    $sel = old('state');
                    foreach ($states as $s) echo '<option value="' . esc($s) . '"' . ($sel === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>City / District <span>*</span></label>
            <input type="text" name="city" value="<?= esc(old('city')) ?>" required placeholder="e.g. Lucknow">
        </div>
        <div class="form-group">
            <label>Brief Description</label>
            <textarea name="description" placeholder="Tell us about your hospital and intended use of ABDM Gateway..."><?= esc(old('description')) ?></textarea>
        </div>

        <!-- Contact Info -->
        <div class="section-title">👤 Contact Information</div>
        <div class="row">
            <div class="form-group">
                <label>Contact Person Name <span>*</span></label>
                <input type="text" name="contact_name" value="<?= esc(old('contact_name')) ?>" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Contact Phone <span>*</span></label>
                <input type="tel" name="contact_phone" value="<?= esc(old('contact_phone')) ?>" required placeholder="10-digit mobile number" pattern="[0-9]{10,15}">
            </div>
        </div>
        <div class="form-group">
            <label>Contact Email <span>*</span></label>
            <input type="email" name="contact_email" value="<?= esc(old('contact_email')) ?>" required placeholder="official@hospital.com">
            <div class="hint">Approval notification will be sent to this email.</div>
        </div>

        <!-- Login Credentials -->
        <div class="section-title">🔐 Portal Login Credentials</div>
        <div class="form-group">
            <label>Desired Username <span>*</span></label>
            <input type="text" name="username" value="<?= esc(old('username')) ?>" required placeholder="e.g. cityhospital_admin" pattern="[a-zA-Z0-9_\-]{4,80}">
            <div class="hint">4–80 characters, letters/numbers/underscore/hyphen only.</div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <input type="password" name="password" id="pw" required minlength="8" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <input type="password" name="confirm_password" id="cpw" required minlength="8" placeholder="Re-enter password">
                <div id="pwMatch"></div>
            </div>
        </div>

        <button type="submit" class="btn">Submit Registration Request</button>
    </form>

    <?php endif; ?>

    <div class="footer-link">Already have an account? <a href="/">Login here</a></div>
</div>
<script>
(function(){
    var p=document.getElementById('pw'), c=document.getElementById('cpw'), m=document.getElementById('pwMatch');
    if (!p || !c) return;
    function chk(){
        if(!c.value){m.textContent='';return}
        if(p.value===c.value){m.textContent='✓ Passwords match';m.style.color='#166534';}
        else{m.textContent='✗ Do not match';m.style.color='#991b1b';}
    }
    p.addEventListener('input',chk); c.addEventListener('input',chk);
})();
</script>
</body>
</html>
