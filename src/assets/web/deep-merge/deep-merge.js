function deepMerge(obj1, obj2) {
    const result = { ...obj1 }; // Start with a shallow copy of obj1
    for (const key in obj2) {
        if (obj2[key] instanceof Object && key in result) {
            result[key] = deepMerge(result[key], obj2[key]); // Recursively merge
        } else {
            result[key] = obj2[key]; // Overwrite if not an object
        }
    }
    return result;
}
