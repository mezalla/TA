import pandas as pd
import re
import mysql.connector
from mysql.connector import Error
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

def get_mysql():
    try:
        connection = mysql.connector.connect(host='localhost',
                                             database='test',
                                             user='root',
                                             password='',
                                             charset='utf8')

        sql_select_Query = "select * from crawl"
        cursor = connection.cursor()
        cursor.execute(sql_select_Query)
        # get all records
        records = cursor.fetchall()
        print("Total number of rows in table: ", cursor.rowcount, "<br/><br/>")

        print("\nPrinting each row", "<br/><br/>")

    except mysql.connector.Error as e:
        print("Error reading data from MySQL table", e)
    finally:
        if  connection.is_connected():
            connection.close()
            cursor.close()
            print("MySQL connection is closed")

            return records

#regex tweet cleaner
def text_cleaner(text):
    dataFrameStopword = pd.read_excel('C:/Users/Admin/Documents/SKRIPSI/stopword.xls')
    dataFrameSlangword = pd.read_excel('C:/Users/Admin/Documents/SKRIPSI/slangword1.xlsx')

    factory_stem = StemmerFactory()
    stemmer = factory_stem.create_stemmer()

    regex = re.compile('[^a-zA-Z]')  # letter only
    regex_2 = re.compile('\s+')  # remove more than one spaces to only one space
    # Letter only process
    text = re.sub(r'http\S+', '', text) #remove url
    text = regex.sub(' ', text)
    text = (text).lstrip(' ')  # remove leading space
    text = stemmer.stem(text)  # stemming

    # Mengubah slangword
    for index in range(len(dataFrameSlangword['slangword'])):
        if dataFrameSlangword['slangword'][index] in text:
            text = re.sub(r'\b{}\b'.format(dataFrameSlangword['slangword'][index]), dataFrameSlangword['kata_asli'][index], text)

    # Menghilangkan stopword
    for index in range(len(dataFrameStopword['stopword'])):
        if dataFrameStopword['stopword'][index] in text:
            text = re.sub(r'\b{}\b'.format(dataFrameStopword['stopword'][index]), '', text)   

    text = regex_2.sub(' ', text)




    return text

#mysql connect to insert
def insert_mysql(tweet_text, label=0):
    """
    connect to MySQL database and insert twitter data
    """
    try:
        con = mysql.connector.connect(host='localhost', database='test', user='root', password='', charset='utf8')

        if con.is_connected():
            """
            Insert twitter data
            """
            cursor = con.cursor()
            # twitter, golf
            query = "INSERT INTO clean (tweet_text, label) VALUES (%s, %s)"
            cursor.execute(query, (tweet_text, label))
            con.commit()


    except Error as e:
        print(e)

    cursor.close()
    con.close()

    return

# ===================================================== end of function


#isi list
tweet_list= [] # Lists to be added as columns(Tweets Text) in our dataframe
label_list= [] # Lists to be added as columns(label) in our dataframe

data_twitter = get_mysql()

for row in data_twitter:
    text_tweet = text_cleaner(row[2])
    tweet_list.append(text_tweet)
    label_list.append(row[3])
    print(text_tweet, "<br/><br/>")

print("======= Preprocessing Finish =========", "<br/><br/>")

df_twitter = pd.DataFrame({
                   'tweet':tweet_list,
                   'label':label_list})

df_twitter['tweet'] = df_twitter['tweet'].str.lower() #casefolding

for row in df_twitter.itertuples():
    insert_mysql(row[1], label=row[2])



