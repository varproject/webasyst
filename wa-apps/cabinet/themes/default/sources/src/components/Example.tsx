import React, { PropsWithChildren } from 'react';
import ReactDomServer from 'react-dom/server';
import prettier from 'prettier';
import { useUniqueId } from "@scompiler/0003-product";

type Props = PropsWithChildren<{}>;

export default function(props: Props) {
    const { children } = props;
    const id = `example-${useUniqueId('example')}`;

    const code = prettier.format(ReactDomServer.renderToStaticMarkup(
        <React.Fragment>
            {children}
        </React.Fragment>
    ), {
        parser: 'html',
        tabWidth: 4,
        printWidth: 90,
    });

    const button = (
        <button
            className="sa-example__button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target={`#${id}`}
            aria-expanded="false"
            aria-controls={id}
        >
            Source Code
        </button>
    );

    return (
        <React.Fragment>
            <div className="sa-example my-5">
                <div className="sa-example__legend">Example</div>
                <div className="sa-example__body">
                    {children}
                </div>
                {button}
                <div className="sa-example__code collapse" id={id}>
                    <pre>
                        <code className="language-html">
                            {code}
                        </code>
                    </pre>
                    {button}
                </div>
            </div>
        </React.Fragment>
    );
}
