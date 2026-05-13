import React from 'react';
import classNames from 'classNames';
import TocList from './TocList';
import { TocItem } from './TocContext';

interface Props {
    items: TocItem[];
    className?: string;
    topLink?: boolean;
}

export default function(props: Props) {
    const { items, className = '', topLink = false } = props;

    let link: React.ReactNode = 'Table of Content';

    if (topLink) {
        link = <a className="sa-toc__link" href={'#top'}>{link}</a>;
    }

    return (
        <div className={classNames('sa-toc', className)}>
            <div className="sa-toc__container">
                <div className="sa-toc__head">
                    {link}
                </div>

                <TocList items={items} />
            </div>
        </div>
    );
}
