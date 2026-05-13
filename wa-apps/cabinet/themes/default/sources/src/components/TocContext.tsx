import React from 'react';

export interface TocItem {
    title: string;
    url: string;
    items?: TocItem[];
}

export interface TocContextValue {
    stack: TocItem[];
    items: TocItem[];
}

const TocContext = React.createContext<TocContextValue>({stack: [], items: []});

export default TocContext;
