import React from 'react';
import suggestions from '../../data/suggestions.json';
import Image from '../../components/Image';

export default function Page() {
    return (
        <React.Fragment>
            {suggestions.map((section, sectionIdx) => (
                <div key={sectionIdx} className="sa-suggestions__section">
                    <div className="sa-suggestions__section-title">{section.title}</div>
                    {section.items.map((item, itemIdx) => (
                        <React.Fragment key={itemIdx}>
                            {item.type === 'action' && (
                                <div className="sa-suggestions__item sa-suggestions__item--type--default">
                                    {item.title}
                                </div>
                            )}
                            {item.type === 'link' && (
                                <div className="sa-suggestions__item sa-suggestions__item--type--link">
                                    {item.title}
                                </div>
                            )}
                            {item.type === 'product' && (
                                <div className="sa-suggestions__item sa-suggestions__item--type--product">
                                    <div className="sa-suggestions__product">
                                        <div className="sa-suggestions__product-image">
                                            <Image src={item.image} size={36} />
                                        </div>
                                        <div className="sa-suggestions__product-info">
                                            <div className="sa-suggestions__product-name">
                                                {item.name}
                                            </div>
                                            <div className="sa-suggestions__product-meta sa-meta">
                                                <ul className="sa-meta__list">
                                                    <li className="sa-meta__item">ID: {item.id}</li>
                                                    <li className="sa-meta__item">Available: {item.available}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </React.Fragment>
                    ))}
                </div>
            ))}
        </React.Fragment>
    );
}

Page.doctype = false;
