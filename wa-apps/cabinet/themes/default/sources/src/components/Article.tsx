import React, { PropsWithChildren } from 'react';
import ReactDomServer from 'react-dom/server';
import Toc from './Toc';
import Breadcrumb, { BreadcrumbItem } from './Breadcrumb';
import TocContext, { TocContextValue } from './TocContext';

type Props = PropsWithChildren<{
    title: string;
    breadcrumb?: BreadcrumbItem[];
    subtitle?: string;
    containerMaxWith?: 'lg' | 'md';
}>;

export default function(props: Props) {
    const {
        title,
        breadcrumb = [],
        subtitle,
        children,
        containerMaxWith = 'md',
    } = props;

    const toc: TocContextValue = {stack: [], items: []};

    ReactDomServer.renderToStaticMarkup(
        <TocContext.Provider value={toc}>
            {children}
        </TocContext.Provider>
    );

    return (
        <div className="sa-article sa-article--has-toc">
            <div className={`sa-article__container container container--max--${containerMaxWith}`}>
                <div className="sa-article__header">
                    {breadcrumb.length > 0 && (
                        <Breadcrumb className="sa-article__breadcrumb" items={breadcrumb} />
                    )}

                    <h1 className="sa-article__title">
                        {title}
                    </h1>

                    {subtitle && (
                        <div className="sa-article__subtitle">
                            {subtitle}
                        </div>
                    )}
                </div>

                <Toc
                    className="sa-article__toc"
                    topLink
                    items={toc.items}
                />

                <div className="sa-article__content">
                    {children}
                </div>
            </div>
        </div>
    );
}
