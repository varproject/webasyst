import React, { PropsWithChildren, useContext } from 'react';
import PageContext from '@scompiler/0003-product/.scompiler/PageContext';
import { useLinks, useScripts, useStyles, useVars } from "@scompiler/0003-product";
import Comment from "@scompiler/0003-product/.scompiler/components/Comment";

type Props = PropsWithChildren<{
    title?: string;
}>;

export default function(props: Props) {
    const { children, title } = props;
    const context = useContext(PageContext);
    const vars = useVars();
    const links = useLinks();
    const styles = useStyles();
    const scripts = useScripts();
    const direction = vars.dir || 'ltr';

    const googleAnalyticsCode = vars.googleAnalytics && `
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '${vars.googleAnalytics}');
    `;

    return (
        <html lang="en" dir={direction} data-scompiler-id={context.id}>
            <head>
                <meta charSet="UTF-8" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="format-detection" content="telephone=no" />
                <title>{title || 'Stroyka Admin - eCommerce Dashboard Template'}</title>

                <Comment value="icon" />
                {/* icon */}
                <link rel="icon" type="image/png" href="images/favicon.png" />

                <Comment value="fonts" />
                {/* fonts */}
                <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i,900,900i" />

                <Comment value="css" />
                {/* css */}
                <link rel="stylesheet" href={`vendor/bootstrap/css/bootstrap.${direction}.css`} />
                <link rel="stylesheet" href="vendor/highlight.js/styles/github.css" />
                <link rel="stylesheet" href="vendor/simplebar/simplebar.min.css" />
                <link rel="stylesheet" href="vendor/quill/quill.snow.css" />
                <link rel="stylesheet" href="vendor/air-datepicker/css/datepicker.min.css" />
                <link rel="stylesheet" href="vendor/select2/css/select2.min.css" />
                <link rel="stylesheet" href="vendor/datatables/css/dataTables.bootstrap5.min.css" />
                <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css" />
                <link rel="stylesheet" href="vendor/fullcalendar/main.min.css" />
                <link rel="stylesheet" href="css/style.css" />

                {links.map((props, idx) => <link key={idx} {...props} />)}
                {styles.map((props, idx) => <style key={idx} {...props} />)}

                {googleAnalyticsCode && (
                    <>
                        <script async src={`https://www.googletagmanager.com/gtag/js?id=${vars.googleAnalytics}`} />
                        <script dangerouslySetInnerHTML={{__html: googleAnalyticsCode}}/>
                    </>
                )}
            </head>
            <body>
                {children}

                <Comment value="scripts" />
                <script src="vendor/jquery/jquery.min.js" />
                <script src="vendor/feather-icons/feather.min.js" />
                <script src="vendor/simplebar/simplebar.min.js" />
                <script src="vendor/bootstrap/js/bootstrap.bundle.min.js" />
                <script src="vendor/highlight.js/highlight.pack.js" />
                <script src="vendor/quill/quill.min.js" />
                <script src="vendor/air-datepicker/js/datepicker.min.js" />
                <script src="vendor/air-datepicker/js/i18n/datepicker.en.js" />
                <script src="vendor/select2/js/select2.min.js" />
                <script src="vendor/fontawesome/js/all.min.js" data-auto-replace-svg="" async />
                <script src="vendor/chart.js/chart.min.js" />
                <script src="vendor/datatables/js/jquery.dataTables.min.js" />
                <script src="vendor/datatables/js/dataTables.bootstrap5.min.js" />
                <script src="vendor/nouislider/nouislider.min.js" />
                <script src="vendor/fullcalendar/main.min.js" />
                <script src="js/stroyka.js" />
                <script src="js/custom.js" />
                <script src="js/calendar.js" />
                <script src="js/demo.js" />
                <script src="js/demo-chart-js.js" />

                {scripts.map((props, idx) => <script key={idx} {...props} />)}
            </body>
        </html>
    );
}
