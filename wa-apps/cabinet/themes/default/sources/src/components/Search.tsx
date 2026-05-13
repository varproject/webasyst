import React from 'react';
import classNames from 'classnames';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';

interface Props {
    className?: string;
}

export default function(props: Props) {
    const { className = '' } = props;
    const svg = useSvg();

    return (
        <form className={classNames('sa-search sa-search--state--pending', className)}>
            <div className="sa-search__body">
                <label className="visually-hidden" htmlFor="input-search">Search for:</label>
                <div className="sa-search__icon">
                    {svg('stroyka/magnifier-16')}
                </div>
                <input
                    id="input-search"
                    className="sa-search__input"
                    type="text"
                    placeholder="Search for the truth"
                    autoComplete="off"
                />
                <button className="sa-search__cancel d-sm-none" type="button" aria-label="Close search">
                    {svg('stroyka/cross-12')}
                </button>
                <div className="sa-search__field" />
            </div>
            <div className="sa-search__dropdown">
                <div className="sa-search__dropdown-loader" />
                <div className="sa-search__dropdown-wrapper">
                    <div className="sa-search__suggestions sa-suggestions" />

                    <div className="sa-search__help sa-search__help--type--no-results">
                        <div className="sa-search__help-title">No results for "<span className="sa-search__query" />"</div>
                        <div className="sa-search__help-subtitle">Make sure that all words are spelled correctly.</div>
                    </div>

                    <div className="sa-search__help sa-search__help--type--greeting">
                        <div className="sa-search__help-title">Start typing to search for</div>
                        <div className="sa-search__help-subtitle">Products, orders, customers, actions, etc.</div>
                    </div>
                </div>
            </div>
            <div className="sa-search__backdrop" />
        </form>
    );
}
