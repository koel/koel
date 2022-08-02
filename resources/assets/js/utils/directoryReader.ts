// Adapted from https://stackoverflow.com/a/53058574
async function readEntriesPromise (directoryReader: FileSystemDirectoryReader): Promise<FileSystemEntry[]> {
  return await new Promise((resolve, reject): void => {
    directoryReader.readEntries(resolve, reject)
  })
}

async function readAllDirectoryEntries (directoryReader: FileSystemDirectoryReader): Promise<FileSystemEntry[]> {
  const entries: FileSystemEntry[] = []
  let readEntries = await readEntriesPromise(directoryReader)

  while (readEntries.length > 0) {
    entries.push(...readEntries)
    readEntries = await readEntriesPromise(directoryReader)
  }

  return entries
}

async function getAllFileEntries (dataTransferItemList: DataTransferItemList) {
  const fileEntries: FileSystemEntry[] = []
  const queue: FileSystemEntry[] = []

  for (let i = 0, length = dataTransferItemList.length; i < length; i++) {
    queue.push(dataTransferItemList[i].webkitGetAsEntry()!)
  }

  while (queue.length > 0) {
    const entry = queue.shift()

    if (!entry) {
      continue
    }

    if (entry.isFile) {
      fileEntries.push(entry)
    } else if (entry.isDirectory) {
      // @ts-ignore
      queue.push(...await readAllDirectoryEntries(entry.createReader()))
    }
  }

  return fileEntries
}

export { getAllFileEntries }
