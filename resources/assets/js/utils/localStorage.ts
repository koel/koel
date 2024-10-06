import { e } from "vitest/dist/index-e0804ba8";

const setItem = (key: string, value: any): void => {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        // do nothing
    }
};

const getItem = <T>(key: string): T | null => {
    try {
        const item = window.localStorage.getItem(key);
        return item ? (JSON.parse(item) as T) : null;
    } catch (e) {
        return null;
    }
};

export const localStorage = {
    setItem,
    getItem
};