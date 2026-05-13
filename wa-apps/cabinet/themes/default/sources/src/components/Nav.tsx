import React from 'react';
import navigation from '../data/navigation.json';
import NavMenu from './NavMenu';

export default function() {
    return (
        <ul className="sa-nav sa-nav--sidebar" data-sa-collapse="">
            {navigation.map((section, idx) => (
                <li key={idx} className="sa-nav__section">
                    <div className="sa-nav__section-title">
                        <span>{section.title}</span>
                    </div>
                    {section.menu && <NavMenu items={section.menu} />}
                </li>
            ))}
        </ul>
    );
}
