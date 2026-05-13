import React from 'react';
import { TocItem } from './TocContext';

interface Props {
    items: TocItem[];
}

export default function TocList(props: Props) {
    const { items } = props;

    return (
        <ul className="sa-toc__list">
            {items.map((item, itemIdx) => (
                <li key={itemIdx} className="sa-toc__item">
                    <a className="sa-toc__link" href={item.url}>{item.title}</a>

                    {item.items && (
                        <TocList items={item.items} />
                    )}
                </li>
            ))}
        </ul>
    );
}
