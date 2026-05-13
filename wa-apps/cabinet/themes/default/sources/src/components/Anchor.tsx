import React, { PropsWithChildren, useContext } from 'react';
import ReactDomServer from 'react-dom/server';
import classNames from 'classNames';
import TocContext, { TocItem } from './TocContext';

type Props = PropsWithChildren<{
    id?: string;
    idPrefix?: string;
    tag?: string;
    className?: string;
}>;

export default function(props: Props) {
    const { idPrefix = '', tag = 'span', className = '', children } = props;
    let { id } = props;

    const toc = useContext(TocContext);
    const title = ReactDomServer.renderToStaticMarkup(<React.Fragment>{children}</React.Fragment>);

    if (typeof id === 'undefined') {
        id = 'article-' + idPrefix + title.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-');
    }

    const level = /^h[1-6]$/.test(tag) ? parseFloat(tag.substr(1)) - 1 : 10;
    const url = `#${id}`;

    const current: TocItem = {
        title: title,
        url: url,
        items: [],
    };

    const itemsStack: TocItem[][] = [toc.items, ...toc.stack.map(x => x.items)];

    if (level > toc.stack.length) {
        toc.stack.push(current);

        itemsStack[itemsStack.length - 1].push(current);
    } else if (level < toc.stack.length) {
        toc.stack.splice(level - 1);
        toc.stack.push(current);

        itemsStack[level - 1].push(current);
    } else {
        toc.stack.pop();
        toc.stack.push(current);

        itemsStack[itemsStack.length - 2].push(current);
    }

    return React.createElement(tag, {
        id,
        className: classNames('sa-anchor', className),
    }, (
        <span className="sa-anchor__body">
            {children}
            <a className="sa-anchor__link" href={url}>#</a>
        </span>
    ));
}
